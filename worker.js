const shadowshareURLs = [
  "https://gitee.com/api/v5/repos/configshare/share/raw/%s?access_token=9019dae4f65bd15afba8888f95d7ebcc&ref=hotfix",
  "https://raw.githubusercontent.com/configshare/share/hotfix/%s",
  "https://shadowshare.v2cross.com/servers/%s",
];

const shadowshareKey = "8YfiQ8wrkziZ5YFW";
const shadowshareIV = "8YfiQ8wrkziZ5YFW";
const cnc07Key = "1kv10h7t*C3f8c@$";
const cnc07IV = "@$6l&bxb5n35c2w9";

async function httpGet(url) {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  return await response.text();
}

async function aesDecrypt(ciphertext, key, iv) {
  const keyBytes = new TextEncoder().encode(key);
  const ivBytes = new TextEncoder().encode(iv);
  const decoded = Uint8Array.from(atob(ciphertext), c => c.charCodeAt(0));
  const cryptoKey = await crypto.subtle.importKey("raw", keyBytes, { name: "AES-CBC" }, false, ["decrypt"]);
  const decrypted = await crypto.subtle.decrypt({ name: "AES-CBC", iv: ivBytes }, cryptoKey, decoded);
  return new TextDecoder().decode(decrypted);
}

async function v2nodes() {
  const resp = await httpGet("https://www.v2nodes.com/");
  const re = /data-config="(.*?)"/;
  const matches = resp.match(re);
  if (!matches || matches.length < 2) {
    throw new Error("Failed to extract subscription link");
  }
  const sublink = matches[1];
  return await httpGet(sublink);
}

async function shadowshare(file) {
  for (const url of shadowshareURLs) {
    try {
      const resp = await httpGet(url.replace("%s", file));
      const decrypted = await aesDecrypt(resp, shadowshareKey, shadowshareIV);
      return decrypted;
    } catch (e) {
      continue;
    }
  }
  throw new Error("All shadowshare URLs failed");
}

async function cnc07() {
  const resp = await httpGet("http://cnc07api.cnc07.com/api/cnc07iuapis");
  const data = JSON.parse(resp);
  if (!data.servers) {
    throw new Error("Invalid servers data");
  }
  const decrypted = await aesDecrypt(data.servers, cnc07Key, cnc07IV);
  const configs = JSON.parse(decrypted);
  let result = "";
  for (const config of configs) {
    if (config.alias) {
      const re = /SS = ss, ([\d.]+), (\d+),encrypt-method=([\w-]+),password=([\w\d]+)/;
      const matches = config.alias.match(re);
      if (matches && matches.length === 5) {
        const [_, ip, port, method, password] = matches;
        const cityCn = config.city_cn || "";
        const city = config.city || "";
        const ssURI = `ss://${method}:${password}@${ip}:${port}#${cityCn} ${city}`;
        result += ssURI + "\n";
      }
    }
  }
  return result;
}

// Convert Unicode string to binary string for btoa()
function stringToBinary(str) {
  return encodeURIComponent(str)
    .replace(/%([0-9A-F]{2})/g, function(match, p1) {
      return String.fromCharCode('0x' + p1);
    });
}

async function getMergeBase64() {
  const v2Merged = atob(await v2nodes());
  const ssMerged = await shadowshare("shadowshareserver");
  const cncMerged = await cnc07();
  const merged = [v2Merged, ssMerged, cncMerged].join("\n");
  return btoa(stringToBinary(merged));
}

addEventListener("fetch", event => {
  event.respondWith(handleRequest(event));
});

async function handleRequest(event) {
  const request = event.request;
  const url = new URL(request.url);
  const forceRefresh = url.searchParams.get("force_refresh") === "true";
  const cacheKey = request.url;
  const cache = caches.default;

  if (forceRefresh) {
    try {
      const result = await getMergeBase64();
      const response = new Response(result, { status: 200 });
      return response;
    } catch (e) {
      return new Response(e.message, { status: 500 });
    }
  }

  let response = await cache.match(cacheKey);
  if (!response) {
    try {
      const result = await getMergeBase64();
      response = new Response(result, { status: 200 });
      response.headers.append("Cache-Control", "s-maxage=21600"); // Cache for 6 hours
      event.waitUntil(cache.put(cacheKey, response.clone()));
    } catch (e) {
      return new Response(e.message, { status: 500 });
    }
  }
  return response;
}