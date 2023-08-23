<?php
ob_start();

$token_params = http_build_query(array(
    "grant_type" => "authorization_code",
    "code" => filter_input(INPUT_GET, "code"),
    "redirect_uri" => getenv("ebrains_redirect_wa"),
    "client_id" => getenv("ebrains_id_wa"),
    "client_secret" => getenv("ebrains_secret_wa")
        ));
$token_ch = curl_init(getenv("ebrains_token"));
curl_setopt_array($token_ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $token_params
));
$token_res = curl_exec($token_ch);
curl_close($token_ch);
$token_obj = json_decode($token_res, true);
$token = $token_obj["access_token"];

$json= json_decode(urldecode(filter_input(INPUT_GET, "state")), true);
$json["token"]=$token;
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <script src="dppick.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/Tevemadar/NetUnzip/inflater.min.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/Tevemadar/NetUnzip/netunzip.min.js"></script>
        <style>
            .error{
                color: red;
                font-weight: bold;
            }
        </style>
        <script>
            const state=<?php echo json_encode($json);?>;
            async function dpjson(params) {
                const response = await fetch(
                        `https://data-proxy.ebrains.eu/api/v1/buckets/${params}`,{
                        headers:{
                            accept:"application/json",
                            authorization:`Bearer ${state.token}`
                        }
                    });
                return response.json();
            }
            async function startup(){
                if(state.hasOwnProperty("filename")){
                    state.embedded=true;
                    location.href="webalign.html?"+encodeURIComponent(JSON.stringify(state));
                    return;
                }
                const choice=await dppick({
                    bucket:state["clb-collab-id"],
                    token:state.token,
                    title:"Select WebAlign descriptor",
                    extensions:[".waln","wwrp"],
                    create:".waln",
                    createplaceholder:"Create new WebAlign series",
                    createnowarn:true,
                    nocancel:true
                });
                if(choice.pick){
                    state.filename=choice.pick;
                    location.href="webalign.html?"+encodeURIComponent(JSON.stringify(state));
                }else{
                    state.filename=choice.create;
                    newseries();
                }
            }
            function newseries(){
                document.getElementById("newseries").hidden=false;
                document.getElementById("filename").innerText="New series "+state.filename;
                document.getElementById("collab").value=state["clb-collab-id"];
                document.getElementById("atlas").selectedIndex=-1;
                trycollect();
            }
            function cancel(){
                document.getElementById("newseries").hidden=true;
                startup();
            }
            
            let collection;
            let ctime;
            function tryshow(){
//                document.getElementById("filebody").innerHTML=
//                        collection.map(item=>`<tr><td>${item.filename.split("/").slice(-1)[0]}</td><td>${item.format}</td>
//                            <td>${item.width}</td><td>${item.height}</td><td>${item.tilesize}</td><td>${item.overlap}</td></tr>`).join("");
                const copy=collection;
                collection=[];
                let error=false;
                document.getElementById("filebody").innerHTML=
                        copy.map(item=>{
                            const filename=item.filename.split("/").slice(-1)[0];
                            const fail=!filename.match(/_s\d+/);
                            if(fail)
                                error=true;
                            else
                                collection.push(item);
                            return `<tr><td ${fail?"class='error'":""}>${filename}</td><td>${item.format}</td>`+
                            `<td>${item.width}</td><td>${item.height}</td><td>${item.tilesize}</td><td>${item.overlap}</td></tr>`;
                        }).join("");
                if(error)
                    document.getElementById("log").innerText="Sections marked in red do not follow the expected numbering convention and will not be part of the series.";
                else if(collection.length)
                    document.getElementById("log").innerText="Ready.";
                document.getElementById("filetable").hidden=copy.length===0;
                document.getElementById("create").disabled=collection.length===0 || document.getElementById("atlas").selectedIndex===-1;
            }
            
            function clear(){
                collection=[];
                oldisv=false;
                tryshow();
            }
            
            function dzisection(dzi,filename){
                return {
                    filename,
                    width:parseInt(dzi.match(/Width="(\d+)"/m)[1]),
                    height:parseInt(dzi.match(/Height="(\d+)"/m)[1]),
                    tilesize:parseInt(dzi.match(/TileSize="(\d+)"/m)[1]),
                    overlap:parseInt(dzi.match(/Overlap="(\d+)"/m)[1]),
                    format:dzi.match(/Format="([^"]+)"/m)[1]
                };
            }
            
            let dzipbundles;
            let bucket;
            const wfprefix=".nesysWorkflowFiles/zippedPyramids/";
            async function trycollect(){
                clear();
                const current=ctime=Date.now();
                const button=document.getElementById("create");
                button.disabled=true;
                const prg=document.getElementById("log");
                document.getElementById("filetable").hidden=true;
                const btns=document.getElementById("dzipbuttons");
                btns.hidden=true;
                dzipbundles=new Map();
                try{
                    bucket=document.getElementById("collab").value.replaceAll(/[^-\w().!]/g, "");
                    const dzips = await dpjson(`${bucket}?prefix=${wfprefix}&limit=10000`);
                    if(dzips.objects.length<2){
                        const result = await dpjson(`${bucket}?delimiter=/&limit=10000`);
                        if(result.hasOwnProperty("objects")){
                            const images=result.objects.filter(item=>item.hasOwnProperty("subdir")&&item.subdir.includes("."));
                            for(const image of images){
                                prg.innerText="Fetching DZI "+(collection.length+1)+"/"+images.length;
                                const subdir=image.subdir;
                                const pos=subdir.lastIndexOf(".");
                                const name=subdir.substring(0,pos);
                                const urljson=await dpjson(`${bucket}/${subdir+name}.dzi?redirect=false`);
                                const dzi=await fetch(urljson.url).then(response=>response.text());
                                if(current!==ctime)
                                    return;
                                collection.push(dzisection(dzi,subdir.substring(0,subdir.length-1)));
                            }
                        }
                    } else {
                        for(const item of dzips.objects) {
                            const parts=item.name.substring(wfprefix.length).split("/");
                            if(parts.length===2 && parts[1].endsWith(".dzip")) {
                                if(!dzipbundles.has(parts[0]))
                                    dzipbundles.set(parts[0],[]);
                                dzipbundles.get(parts[0]).push(parts[1]);
                            }
                        }
                        dzipbundles.forEach((v,k)=>btns.innerHTML+=`<button onclick="dzicollect('${k}')">${k} (${v.length})</button> `);
                        btns.hidden=dzipbundles.size===0;
                    }
                }catch(ex){console.log(ex);}
                tryshow();
            }
            async function dzicollect(dzipbundle){
                clear();
                const current=ctime=Date.now();
                const prg=document.getElementById("log");
                const dzips=dzipbundles.get(dzipbundle);
                for(let i=0;i<dzips.length;i++) {
                    if(current!==ctime)return;
                    prg.innerText="Fetching DZI "+(collection.length+1)+"/"+dzips.length;
                    const dzip=dzips[i];
                    const zipdir=await netunzip(
                            ()=>dpjson(`${bucket}/${wfprefix}${dzipbundle}/${dzip}?redirect=false`).then(json=>json.url));
                    for(const [_,entry] of zipdir.entries) {
                        if(entry.name.endsWith(".dzi")) {
                            const data=await zipdir.get(entry);
                            if(current!==ctime)return;
                            const dzi=new TextDecoder().decode(data);
                            collection.push(dzisection(dzi,dzipbundle+"/"+dzip));
                            break;
                        }
                    }
                }
                tryshow();
            }
            // https://object.cscs.ch/v1/AUTH_08c08f9f119744cbbf77e216988da3eb/imgsvc-be74b890-2c14-4404-b187-678ab8cacc9e/ext-d000018_mouse3_calb_s193.tif/ext-d000018_mouse3_calb_s193.dzi
            // https://localizoom.apps.hbp.eu/filmstripzoom.html?atlas=ABA_Mouse_CCFv3_2017_25um&series=https://object.cscs.ch/v1/AUTH_4791e0a3b3de43e2840fe46d9dc2b334/ext-d000018_CalbindinDistr-NormalMouse_pub/Mouse3/mouse3_nonlinear_lz.json&pyramids=imgsvc-be74b890-2c14-4404-b187-678ab8cacc9e&tools&nl
            let oldisv;
            async function import_link(event) {
                clear();
                const link=event.target.value;
                if(!link.startsWith("https://localizoom.apps.hbp.eu/filmstripzoom.html?"))
                    return;
                const params=link.split("?")[1].split("&").reduce((acc,item)=>{
                    const pair=item.split("=");
                    acc.set(pair[0],pair.length===1?true:pair[1]);
                    return acc;
                },new Map());
                
                if(params.get("pyramids").startsWith("buckets/")){
                    bucket=document.getElementById("collab").value=params.get("pyramids").substring("buckets/".length);
                }else{
                    bucket=false;
                    document.getElementById("collab").value="---";
                    oldisv=params.get("pyramids");
                }
                
                const select=document.getElementById("atlas");
                atlas.selectedIndex=-1;
                for(let i=0;i<atlas.options.length;i++)
                    if(atlas.options[i].value===params.get("atlas"))
                        atlas.selectedIndex=i;
                
//                bucket=document.getElementById("collab").value=params.get("pyramids").substring("buckets/".length);
                const series=await fetch(params.get("series")).then(response=>response.json());
                collection=await Promise.all(series.slices.map(async slice=>{
                    const filename=slice.filename;
                    const name=filename.substring(0,filename.lastIndexOf("."))
                    let dzi,section;
                    try {
                        dzi=await fetch((bucket?"https://data-proxy.ebrains.eu/api/v1/buckets/":"https://object.cscs.ch/v1/AUTH_08c08f9f119744cbbf77e216988da3eb/")+
                            `${bucket?bucket:oldisv}/${filename}/${name}.dzi`)
                            .then(response=>response.text());
                        section=dzisection(dzi,filename);
                    } catch(ex) {
                        dzi=await fetch((bucket?"https://data-proxy.ebrains.eu/api/v1/buckets/":"https://object.cscs.ch/v1/AUTH_08c08f9f119744cbbf77e216988da3eb/")+
                            `${bucket?bucket:oldisv}/${name}.tif/${name}.dzi`)
                            .then(response=>response.text());
                        section=dzisection(dzi,name+".tif");
                    }
                    if(slice.hasOwnProperty("anchoring"))
                        section.ouv=slice.anchoring;
                    if(slice.hasOwnProperty("markers"))
                        section.markers=slice.markers.map(marker=>({
                            x:marker[0]*section.width/slice.width,
                            y:marker[1]*section.height/slice.height,
                            nx:marker[2]*section.width/slice.width,
                            ny:marker[3]*section.height/slice.height
                        }));
                    return section;
                }));
                tryshow();
            }
            async function create(){
                document.getElementById("create").disabled=true;
                const series={
                    atlas:document.getElementById("atlas").value,
                    sections:collection
                };
                if(bucket)series.bucket=bucket;
                if(oldisv)series.oldisv=oldisv;
                const upload=await fetch(
                        `https://data-proxy.ebrains.eu/api/v1/buckets/${state["clb-collab-id"]}/${state.filename}`,{
                            method: "PUT",
                            headers:{
                                accept:"application/json",
                                authorization:`Bearer ${state.token}`
                            }
                        }
                    ).then(response=>response.json());
                if (!upload.hasOwnProperty("url")) {
                    document.getElementById("log").innerHTML=("Possible error happened:<br>" + JSON.stringify(upload));
                    return;
                }
                await fetch(upload.url, {
                    method: "PUT",
                    headers: {
                        'Content-Type': 'application/x.webalign'
                    },
                    body: JSON.stringify(series)
                });
                location.href="webalign.html?"+encodeURIComponent(JSON.stringify(state));
            }
        </script>
    </head>
    <body onload="startup()">
        <div id="newseries" hidden>
            <div id="filename"></div>
            <input oninput="import_link(event)" placeholder="Import LocaliZoom link"><br><br>
            Enter name of image-chunk collab: <input id="collab" oninput="trycollect()"><br>
            Target atlas:
            <select id="atlas" onchange="tryshow()">
                <option value="WHS_SD_Rat_v4_39um">WHS SD Rat v4 39um</option>
                <option value="WHS_SD_Rat_v3_39um">WHS SD Rat v3 39um</option>
                <option value="ABA_Mouse_CCFv3_2017_25um">ABA Mouse CCFv3 2017 25um</option>
            </select><br>
            <button id="create" onclick="create()" disabled>Create</button><button onclick="cancel()">Cancel</button>
            <pre id="log"></pre>
            <div id="dzipbuttons"></div>
            <table id="filetable">
                <thead>
                    <th>name</th><th>format</th><th>width</th><th>height</th><th>tilesize</th><th>overlap</th>
                </thead>
                <tbody id="filebody"></tbody>
            </table>
        </div>
    </body>
</html>
