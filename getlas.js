postMessage("Initializing loader");
let start = Date.now();

(async()=>{
    const atlases=await caches.open("atlases");
    let json=await atlases.match(location.href+".json");
    let rle=await atlases.match(location.href+".rle");
    console.log("Checked cache",Date.now() - start);
    if(json && rle){
        importScripts("derle.js");
        json=await json.json();
        rle=new Uint8Array(await rle.arrayBuffer());
        console.log("Got atlas from cache",Date.now() - start);
        let message = "Decoding atlas<br>" + location.search.substring(1).replaceAll("_", " ") + "<br>";
        const data = derle(rle, json.encoding, (x, y) => postMessage(message + (x / y * 100).toFixed(0) + "%"));
        console.log(Date.now() - start, "Decoded:", data.length);
        json.blob = data.buffer;
        postMessage(json, [json.blob]);
    }else{
        
importScripts("inflater.js", "derle.js");
let name = location.search.substring(1);
let message = "Loading atlas<br>" + name.replaceAll("_", " ") + "<br>";
postMessage(message + "0%");
let xhr = new XMLHttpRequest();
//xhr.open("GET", name + ".json", false);
xhr.open("GET", "https://data-proxy.ebrains.eu/api/v1/buckets/quint-atlas-binaries/LZ-flatpacks/" + name + ".json", false);
xhr.responseType = "json";
xhr.send();
let json = xhr.response;
for (let label of json.labels)
    if (label.hasOwnProperty("rgb")) {
        let rgb = parseInt(label.rgb, 16);
        label.r = rgb >> 16;
        label.g = (rgb >> 8) & 255;
        label.b = rgb & 255;
    }

await atlases.put(location.href+".json",new Response(JSON.stringify(json)));

console.log(Date.now() - start, "Got json");
xhr = new XMLHttpRequest();
//xhr.open("GET",name+".pack",false);
//xhr.open("GET", name + ".pack");
xhr.open("GET", "https://data-proxy.ebrains.eu/api/v1/buckets/quint-atlas-binaries/LZ-flatpacks/" + name + ".pack");
xhr.responseType = "arraybuffer";
//xhr.send();
xhr.onprogress = event => {
    postMessage(message + (event.loaded / event.total * 10).toFixed(0) + "%");
};
xhr.onload = async() => {
    let pack = xhr.response;
    console.log(Date.now() - start, "Got pack");
    let data = new Uint8Array(pack);
    console.log(Date.now() - start, "Size:", data.length);
    data = inflate(data, {progress: (x, y) => postMessage(message + (10 + x / y * 20).toFixed(0) + "%")});
    console.log(Date.now() - start, "Inflated:", data.length);
    await atlases.put(location.href+".rle",new Response(data));
    data = derle(data, json.encoding, (x, y) => postMessage(message + (30 + x / y * 70).toFixed(0) + "%"));
    console.log(Date.now() - start, "Decoded:", data.length);
    json.blob = data.buffer;
    postMessage(json, [json.blob]);
};
xhr.send();

    }
})();
