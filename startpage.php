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
        <script>
            const state=<?php echo json_encode($json);?>;
            async function startup(){
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
                trycollect();
            }
            function cancel(){
                document.getElementById("newseries").hidden=true;
                startup();
            }
            
            let collecting;
            let recollect;
            let collection;
            let bucket;
            async function trycollect(){
                if(collecting){
                    recollect=true;
                    return;
                }
                const button=document.querySelector("button");
                button.disabled=true;
                const prg=document.getElementById("log");

                collecting=true;
                collection=[];
                
                try{
                    bucket=document.getElementById("collab").value.replaceAll(/[^-\w().!]/g, "");
                    const result=await fetch(
                            `https://data-proxy.ebrains.eu/api/v1/buckets/${bucket}?delimiter=/`,{
                                headers:{
                                    accept:"application/json",
                                    authorization:`Bearer ${state.token}`
                                }
                            }
                        ).then(response=>response.json());
                    if(result.hasOwnProperty("objects")){
                        const images=result.objects.filter(item=>item.hasOwnProperty("subdir")&&item.subdir.includes("."));
                        for(const image of images){
                            prg.innerText="Fetching DZI "+(collection.length+1)+"/"+images.length;
                            const subdir=image.subdir;
                            const pos=subdir.lastIndexOf(".");
                            const name=subdir.substring(0,pos);
                            const url=await fetch(
                                    `https://data-proxy.ebrains.eu/api/v1/buckets/${bucket}/${subdir+name}.dzi?redirect=false`,{
                                        headers:{
                                            accept:"application/json",
                                            authorization:`Bearer ${state.token}`
                                        }
                                    }
                                ).then(response=>response.json()).then(json=>json.url);
                            const dzi=await fetch(url).then(response=>response.text());
                            collection.push({
                                filename:subdir.substring(0,subdir.length-1),
                                width:parseInt(dzi.match(/Width="(\d+)"/m)[1]),
                                height:parseInt(dzi.match(/Height="(\d+)"/m)[1]),
                                tilesize:parseInt(dzi.match(/TileSize="(\d+)"/m)[1]),
                                overlap:parseInt(dzi.match(/Overlap="(\d+)"/m)[1]),
                                format:dzi.match(/Format="([^"]+)"/m)[1]
                            });
                        }
                    }
                }catch(ex){console.log(ex);}
                
                collecting=false;
                if(recollect){
                    recollect=false;
                    trycollect();
                }else{
                    prg.innerText="Raw series:\n"+JSON.stringify(collection,null,1);
                    button.disabled=collection.length===0;
                }
            }
            async function create(){
                document.querySelector("button").disabled=true;
                const series={
                    bucket,
                    atlas:document.getElementById("atlas").value,
                    sections:collection
                };
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
                    document.write("Possible error happened:<br>" + JSON.stringify(upload));
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
            Enter name of image-chunk collab: <input id="collab" oninput="trycollect()"><br>
            Target atlas:
            <select id="atlas">
                <option value="WHS_SD_Rat_v4_39um">WHS SD Rat v4 39um</option>
                <option value="WHS_SD_Rat_v3_39um">WHS SD Rat v3 39um</option>
                <option value="ABA_Mouse_CCFv3_2017_25um">ABA Mouse CCFv3 2017 25um</option>
            </select><br>
            <button onclick="create()" disabled>Create</button><button onclick="cancel()">Cancel</button><br>
            <pre id="log"></pre>
        </div>
    </body>
</html>
