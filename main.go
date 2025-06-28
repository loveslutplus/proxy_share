package main

import (
	"crypto/aes"
	"crypto/cipher"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"regexp"
	"strings"
)

const (
	shadowshareKey = "8YfiQ8wrkziZ5YFW"
	shadowshareIV  = "8YfiQ8wrkziZ5YFW"
	cnc07Key       = "1kv10h7t*C3f8c@$"
	cnc07IV        = "@$6l&bxb5n35c2w9"
)

var shadowshareURLs = []string{
	"https://gitee.com/api/v5/repos/configshare/share/raw/%s?access_token=9019dae4f65bd15afba8888f95d7ebcc&ref=hotfix",
	"https://raw.githubusercontent.com/configshare/share/hotfix/%s",
	"https://shadowshare.v2cross.com/servers/%s",
}

func httpGet(url string) (string, error) {
	resp, err := http.Get(url)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", err
	}
	return string(body), nil
}

func aesDecrypt(ciphertext, key, iv string) (string, error) {
	block, err := aes.NewCipher([]byte(key))
	if err != nil {
		return "", err
	}
	decoded, err := base64.StdEncoding.DecodeString(ciphertext)
	if err != nil {
		return "", err
	}
	if len(decoded) < aes.BlockSize {
		return "", fmt.Errorf("ciphertext too short")
	}
	mode := cipher.NewCBCDecrypter(block, []byte(iv))
	mode.CryptBlocks(decoded, decoded)
	padding := int(decoded[len(decoded)-1])
	if padding > aes.BlockSize {
		return "", fmt.Errorf("invalid padding")
	}
	return string(decoded[:len(decoded)-padding]), nil
}

func v2nodes() (string, error) {
	resp, err := httpGet("https://www.v2nodes.com/")
	if err != nil {
		return "", err
	}
	re := regexp.MustCompile(`data-config="(.*?)"`)
	matches := re.FindStringSubmatch(resp)
	if len(matches) < 2 {
		return "", fmt.Errorf("failed to extract subscription link")
	}
	sublink := matches[1]
	return httpGet(sublink)
}

func shadowshare(file string) (string, error) {
	for _, url := range shadowshareURLs {
		resp, err := httpGet(fmt.Sprintf(url, file))
		if err != nil {
			continue
		}
		decrypted, err := aesDecrypt(resp, shadowshareKey, shadowshareIV)
		if err == nil {
			return decrypted, nil
		}
	}
	return "", fmt.Errorf("all shadowshare URLs failed")
}

func cnc07() (string, error) {
	resp, err := httpGet("http://cnc07api.cnc07.com/api/cnc07iuapis")
	if err != nil {
		return "", err
	}
	var data map[string]interface{}
	if err := json.Unmarshal([]byte(resp), &data); err != nil {
		return "", err
	}
	servers, ok := data["servers"].(string)
	if !ok {
		return "", fmt.Errorf("invalid servers data")
	}
	decrypted, err := aesDecrypt(servers, cnc07Key, cnc07IV)
	if err != nil {
		return "", err
	}
	var configs []map[string]interface{}
	if err := json.Unmarshal([]byte(decrypted), &configs); err != nil {
		return "", err
	}
	var result strings.Builder
	re := regexp.MustCompile(`SS = ss, ([\d.]+), (\d+),encrypt-method=([\w-]+),password=([\w\d]+)`)

	for _, config := range configs {
		alias, ok := config["alias"].(string)
		if !ok {
			continue
		}
		matches := re.FindStringSubmatch(alias)
		if len(matches) == 5 {
			ip, port, method, password := matches[1], matches[2], matches[3], matches[4]
			cityCn, _ := config["city_cn"].(string)
			city, _ := config["city"].(string)
			ssURI := fmt.Sprintf("ss://%s:%s@%s:%s#%s %s", method, password, ip, port, cityCn, city)
			result.WriteString(ssURI + "\n")
		}
	}
	return result.String(), nil
}

func main() {
	typeParam := os.Getenv("TYPE")
	if typeParam == "" {
		typeParam = "merge_base64"
	}

	var result string
	var err error

	switch typeParam {
	case "v2nodes_base64":
		result, err = v2nodes()
	case "v2nodes":
		data, err := v2nodes()
		if err == nil {
			decoded, err := base64.StdEncoding.DecodeString(data)
			if err == nil {
				result = string(decoded)
			} else {
				err = fmt.Errorf("base64 decode failed: %v", err)
			}
		}
	case "shadowshare":
		result, err = shadowshare("shadowshareserver")
	case "shadowshare_base64":
		data, err := shadowshare("shadowshareserver")
		if err == nil {
			result = base64.StdEncoding.EncodeToString([]byte(data))
		}
	case "shadowshare_sub":
		result, err = shadowshare("sub")
	case "cnc07":
		result, err = cnc07()
	case "cnc07_base64":
		data, err := cnc07()
		if err == nil {
			result = base64.StdEncoding.EncodeToString([]byte(data))
		}
	case "merge":
		v2, err1 := v2nodes()
		ss, err2 := shadowshare("shadowshareserver")
		cnc, err3 := cnc07()
		if err1 == nil && err2 == nil && err3 == nil {
			v2Decoded, _ := base64.StdEncoding.DecodeString(v2)
			result = string(v2Decoded) + "\n" + ss + "\n" + cnc
		} else {
			err = fmt.Errorf("merge failed: %v, %v, %v", err1, err2, err3)
		}
	case "merge_base64":
		v2, err1 := v2nodes()
		ss, err2 := shadowshare("shadowshareserver")
		cnc, err3 := cnc07()
		if err1 == nil && err2 == nil && err3 == nil {
			v2Decoded, _ := base64.StdEncoding.DecodeString(v2)
			merged := string(v2Decoded) + "\n" + ss + "\n" + cnc
			result = base64.StdEncoding.EncodeToString([]byte(merged))
		} else {
			err = fmt.Errorf("merge_base64 failed: %v, %v, %v", err1, err2, err3)
		}
	case "clash_http":
		result, err = shadowshare("clash_http_encrypt")
	case "clash_https":
		result, err = shadowshare("clash_https_encrypt")
	case "clash_socks5":
		result, err = shadowshare("clash_socks5_encrypt")
	default:
		result = fmt.Sprintf("不支持的 type 类型：%s", typeParam)
	}

	if err != nil {
		fmt.Println(err)
	} else {
		fmt.Println(result)
	}
}
