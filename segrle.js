function segrle({aid,width,height,data}){
    let buf=new Uint8Array(width*height);
    let pos=0;
    const te=new TextEncoder();
    bytes(te.encode("SegRLEv1"));
    
    const aidbuf=te.encode(aid);
    code(aidbuf.length);
    bytes(aidbuf);
    
    const stats=new Map();
    for(const v of data)
        stats.set(v,stats.has(v)?stats.get(v)+1:1);
    code(stats.size);
    const bytemode=stats.size<=256;
    let codes=new Map();
    for(const pair of [...stats.entries()].sort((x,y)=>y[1]-x[1])){
        code(atlas.remap[pair[0]]);
        codes.set(pair[0],codes.size);
    }
    
    code(width);
    code(height);

    let current=-1;
    let count=0;
    for(const v of data){
        if(v!==current){
            if(current!==-1){
                if(bytemode)
                    byte(codes.get(current));
                else
                    code(codes.get(current));
                code(count);
            }
            current=v;
            count=0;
        }
        else count++;
    }
    if(bytemode)
        byte(codes.get(current));
    else
        code(codes.get(current));
    code(count);

    resize(pos);
    return buf;
    
    function resize(newsize){
        const newbuf=new Uint8Array(newsize);
        const len=Math.min(buf.length,newsize);
        for(let i=0;i<len;i++)
            newbuf[i]=buf[i];
        buf=newbuf;
    }
    function byte(b){
        if(buf.length<=pos)
            resize(buf.length*2);
        buf[pos]=b;
        pos++;
    }
    function bytes(bs){
        for(const b of bs)
            byte(b);
    }
    function code(c){
        while(c>127){
            byte(c|128);
            c>>>=7;
        }
        byte(c);
    }
}