function load_json(event) {
    const fr = new FileReader();
    fr.onload = event => {
        const data = JSON.parse(event.target.result);
        const slices=data.slices;
//        if(slices.length !== images.length) {
//            alert(`Series mismatch: ${slices.length} vs ${images.length} sections.\nProcessing aborts now.`);
//            return;
//        }
//        const checks=new Set;
        const mapped=new Map;
        for(const image of images) {
            const m=image.filename.match(/.*(_s\d+[a-zA-Z]?)/);
            if(!m){
                alert("This function can not yet deal with series without section numbers.\nProblematic section: "+image.filename+"\nProcessing aborts now.");
                return;
            }
            if(mapped.has(m[1])){
                alert("Duplicate section number:\n"+image.filename+"\nProcessing aborts now.");
                return;
            }
//            checks.add(m[1]);
            mapped.set(m[1],image);
        }
//        if(checks.size !== images.length){
//            alert(`${checks.size} individual section numbers are found for ${images.length} sections.\nProcessing aborts now.`);
//            return;
//        }
        for(const slice of slices){
            const m=slice.filename.match(/.*(_s\d+[a-zA-Z]?)/);
            if(!m) {
                alert("This function can not yet deal with series without section numbers.\nProblematic section: "+slice.filename+"\nProcessing aborts now.");
                return;
            }
//            if(!checks.has(m[1])) {
//                alert("Can not find matching section for\n"+slice.filename+"\nProcessing aborts now.");
//                return;
//            }
//            checks.delete(m[1]);
        }
        for(const slice of slices){
            const m=slice.filename.match(/.*(_s\d+[a-zA-Z]?)/);
            const image=mapped.get(m[1]);
            if(image){
                if(slice.anchoring){
                    image.ouv=slice.anchoring;
                    image.anchored=true;
                    image.wadone=true;
                }else{
                    image.anchored=false;
                    image.wadone=false;
                }
                const section=series.sections[images.indexOf(image)];
                if(slice.markers){
                    image.wwdone=true;
                    section.wwdone=true;
                    section.markers=slice.markers.map(coords=>[
                        coords[0]*section.width/slice.width,
                        coords[1]*section.height/slice.height,
                        coords[2]*section.width/slice.width,
                        coords[3]*section.height/slice.height
                    ]);
                }else{
                    delete image.wwdone;
                    delete section.wwdone;
                    delete section.markers;
                }
            }
        }
        propagate();
        activateslice(series.current);
    };
    fr.readAsText(event.target.files[0]);
}
//let json_process;
//function load_json(event) {
//    const fr = new FileReader();
//    fr.onload = event => {
//        const data = JSON.parse(event.target.result);
//        json_process = data;
//        transform_json();
//    };
//    fr.readAsText(event.target.files[0]);
//}
//function transform_json() {
//    const apply=document.getElementById("apply_json");
//    apply.disabled=true;
//    const trf = document.getElementById("json_trf").value;
//    const parts = trf.split(",").map(part => part.split("="));
//    let done=true;
//    let rows = "";
//    for (const va of json_process.slices) {
//        let name=va.filename;
//        if (parts)
//            for (const part of parts)
//                name = name.replaceAll(part[0], part.length > 1 ? part[1] : "");
//        const _s = name.match(/(_s\d+)/);
//        let pair = "?";
//        
//        if (_s) {
//            for (const wa of images)
//                if (wa.filename.includes(_s[0]))
//                    pair = wa.filename;
//        }
//        let match = 0;
//        while (match < name.length && match < pair.length && name[match] === pair[match])
//            match++;
//        rows += `<tr><td>${name.substring(0, match)}<span style="font-weight:bold;color:red">${name.substring(match)}</span></td>
//                           <td>${pair.substring(0, match)}<span style="font-weight:bold;color:red">${pair.substring(match)}</span></td></tr>`;
//        if(name!==pair)done=false;
//    }
//    apply.disabled=!done;
//    document.getElementById("json_pairs").innerHTML = rows;
//}
//function apply_json(){
//    
//}
