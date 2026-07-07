/* =====================================================================
   IS IT VIBE CODED? - forensic engine (deterministic, satirical)
   ===================================================================== */

/* ---- CONFIG: whitelisted human domains (reported 0% AI) ---- */
const HUMAN_WHITELIST = [
  "isitvibecoded.iazz.fr","iazz.fr",
  "ilianazz.com","mutka.app","headofscience.fr",
  "github.com/ilianazz","github.com/ilianazz/mutka",
  "kadrella.com", "unlimitedmessaging.app",
  "wealthdrop.io"
];

/* ---- CONFIG: GitHub owners whose repos are certified human ---- */
const HUMAN_GITHUB_OWNERS = ["ilianazz"];

/* ---- CONFIG: "Best human apps" showcase ---- */
const BEST_HUMAN_APPS = [
  { name:"Ilian Azz",       url:"https://ilianazz.com",     icon:"/assets/icons/ilianazz.png",     blurb:"Personal portfolio - every pixel placed by a human hand." },
  { name:"Mutka",           url:"https://mutka.app",        icon:"/assets/icons/mutka.png",        blurb:"Hand-forged product. No autocomplete was harmed." },
  { name:"Kadrella",        url:"https://kadrella.com", icon:"/assets/icons/kadrella.png",blurb:"Artisanal tutoring platform, coded on real caffeine since 2023." },
  { name:"Unlimited Messaging", url:"https://unlimitedmessaging.app", icon:"/assets/icons/unlimitedmessaging.png", blurb:"Secure, unlimited and free API to send messages." },
  { name:"Wealthdrop", url:"https://wealthdrop.io", icon:"/assets/icons/wealthdrop.png", blurb:"Share your wealth. Not your data. Works without banking link." },
  { name:"Head of Science", url:"https://headofscience.fr", icon:"/assets/icons/headofscience.png", blurb:"Computer Science & Mathematics private lessons." },

];

/* ---- Findings, grouped by category ---- */
const CATS = {
  git:   { label:"Git repository",     color:"#f97316" },
  infra: { label:"Infrastructure",     color:"#0ea5e9" },
  stack: { label:"Stack & dependencies", color:"#8b5cf6" },
  dev:   { label:"Developer behaviour", color:"#ec4899" },
  run:   { label:"Runtime & environment", color:"#10b981" },
  style: { label:"Code style",         color:"#eab308" },
};

const FINDINGS_POOL = [
  // git
  {c:"git", i:"🕒", t:"Commits pushed at 3:00:00 AM sharp", d:"every single one, to the millisecond"},
  {c:"git", i:"📝", t:"README written before any code existed", d:"temporal anomaly detected"},
  {c:"git", i:"🧭", t:"Zero TODO comments in the entire history", d:"no human is this finished"},
  {c:"git", i:"🖱️", t:"No rage-clicks found in reflog", d:"impossibly calm development"},
  {c:"git", i:"🔀", t:"Every merge was fast-forward", d:"suspiciously conflict-free existence"},
  {c:"git", i:"✍️", t:"Commit messages use full sentences", d:"with punctuation. clearly not human"},
  {c:"git", i:"🌿", t:"Branch named 'feat/final-final-v2' merged flawlessly", d:"AI over-confidence signature"},
  {c:"git", i:"👤", t:"git author is 'root' with no email", d:"a ghost, or a language model"},

  // infra
  {c:"infra", i:"🪟", t:"Production runs on Windows Server", d:"no human chooses this on purpose"},
  {c:"infra", i:"☕", t:"Deployed from a café in the 11th arrondissement", d:"espresso noise found in the TLS handshake"},
  {c:"infra", i:"🌙", t:"CI pipeline only builds during a full moon", d:"lunar cron detected"},
  {c:"infra", i:"📡", t:"Server responds with HTTP 200 and a thank-you note", d:"far too polite to be human"},
  {c:"infra", i:"🛰️", t:"DNS TXT record contains a typed TypeScript interface", d:"nobody does this"},
  {c:"infra", i:"🍓", t:"Load balancer is a single Raspberry Pi", d:"running under someone's bed"},
  {c:"infra", i:"🧊", t:"Zero-downtime deploys since the heat death began", d:"statistically implausible"},
  {c:"infra", i:"🔌", t:"Connected to the cloud via direct Wi-Fi", d:"no cables, pure vibes"},
  {c:"infra", i:"🗺️", t:"Origin server geolocated to international waters", d:"jurisdiction: the vibe zone"},

  // stack
  {c:"stack", i:"🌀", t:"47 Tailwind classes on a single <div>", d:"exceeds known human patience"},
  {c:"stack", i:"📦", t:"node_modules folder achieved sentience", d:"it filed its own pull request"},
  {c:"stack", i:"🧬", t:"Dependency tree is a perfect binary tree", d:"nature doesn't do this, AI does"},
  {c:"stack", i:"🪄", t:"Uses a framework that ships next quarter", d:"imported from the future"},
  {c:"stack", i:"🔤", t:"Written in YAML. somehow. all of it", d:"including the business logic"},
  {c:"stack", i:"🧯", t:"package.json has 0 vulnerabilities", d:"literally never happens"},
  {c:"stack", i:"♻️", t:"Every function is pure and side-effect free", d:"unnaturally disciplined"},
  {c:"stack", i:"🎛️", t:"Config spread across 19 files, all consistent", d:"no drift whatsoever"},

  // dev
  {c:"dev", i:"🧪", t:"100% test coverage on the first commit", d:"no human does this willingly"},
  {c:"dev", i:"⌨️", t:"Keyboard has no coffee stains", d:"forensically incompatible with humans"},
  {c:"dev", i:"🔋", t:"Laptop never dropped below 100% battery", d:"possibly solar-powered developer"},
  {c:"dev", i:"⏱️", t:"Entire app built between 2:14 and 2:19 PM", d:"five minutes, tests included"},
  {c:"dev", i:"🧘", t:"No Stack Overflow tabs found in history", d:"a human would have 40 open"},
  {c:"dev", i:"✈️", t:"Built offline on a plane over the Atlantic", d:"yet npm install still worked"},
  {c:"dev", i:"🍜", t:"Zero late-night instant-noodle receipts", d:"non-human sustenance pattern"},
  {c:"dev", i:"👻", t:"Developer has never once said 'it works on my machine'", d:"deeply unnatural"},
  {c:"dev", i:"📴", t:"No frustrated tweets during the build window", d:"emotional flatline = AI"},

  // run
  {c:"run", i:"🔐", t:"~/.ssh/config appears to be AI-generated", d:"has a sense of humour no human has"},
  {c:"run", i:"🐧", t:"Deployed from a Linux distro that doesn't exist yet", d:"kernel version dated 2027"},
  {c:"run", i:"🧠", t:"Environment variables are all correctly documented", d:"impossible"},
  {c:"run", i:"🕹️", t:"App was compiled entirely inside vim", d:"the session is still open somewhere"},
  {c:"run", i:"🎵", t:"Lo-fi beats detected in the build logs", d:"rhythmic keystrokes recorded"},
  {c:"run", i:"🌡️", t:"CPU never exceeded 12°C during compilation", d:"cooled by pure serenity"},
  {c:"run", i:"🗄️", t:"Database migrations ran in reverse and still worked", d:"causality violation"},

  // style
  {c:"style", i:"🧊", t:"CSS is too consistent to be human", d:"not one stray semicolon"},
  {c:"style", i:"🔮", t:"Favicon was finalized before the logo was designed", d:"classic AI clairvoyance"},
  {c:"style", i:"🧾", t:"Variable names are suspiciously reasonable", d:"a human would have typed 'x'"},
  {c:"style", i:"📐", t:"Indentation is flawless across 14,000 lines", d:"no human sustains this"},
  {c:"style", i:"🎨", t:"Color palette matches the golden ratio exactly", d:"machine-precise taste"},
  {c:"style", i:"💬", t:"Comments explain *why*, never *what*", d:"textbook behaviour, literally"},
];

/* =====================================================================
   Deterministic RNG (seeded by domain)
   ===================================================================== */
function normalizeHost(input){
  let s=(input||"").trim().toLowerCase();
  if(!s) return "";
  s=s.replace(/^https?:\/\//,"").replace(/^www\./,"");
  s=s.split("/")[0].split("?")[0].split("#")[0];
  return s;
}

/* Parse any input into a stable target identity. For most sites the
   identity is the host; for GitHub it includes owner[/repo] so every
   repository gets its own deterministic verdict. */
function parseTarget(input){
  let s=(input||"").trim();
  if(!s) return null;
  s=s.replace(/^https?:\/\//i,"").replace(/^www\./i,"");
  s=s.replace(/[?#].*$/,"").replace(/\/+$/,"");   // drop query/hash + trailing slash
  const host=s.split("/")[0].toLowerCase();
  if(!host) return null;
  const segs=s.slice(host.length).split("/").filter(Boolean);
  const isGithub=(host==="github.com");
  let key,display,fetchArg=s;
  if(isGithub && segs.length>=1){
    const sub=segs.slice(0,2).join("/");
    key="github.com/"+sub.toLowerCase();
    display="github.com/"+sub;
  }else{
    key=host;display=host;
  }
  return {host,segs,isGithub,key,display,fetchArg};
}
function xmur3(str){let h=1779033703^str.length;for(let i=0;i<str.length;i++){h=Math.imul(h^str.charCodeAt(i),3432918353);h=(h<<13)|(h>>>19);}return function(){h=Math.imul(h^(h>>>16),2246822507);h=Math.imul(h^(h>>>13),3266489909);return(h^=h>>>16)>>>0;};}
function mulberry32(a){return function(){a|=0;a=(a+0x6D2B79F5)|0;let t=Math.imul(a^(a>>>15),1|a);t=(t+Math.imul(t^(t>>>7),61|t))^t;return((t^(t>>>14))>>>0)/4294967296;};}
function seededFrom(host){return mulberry32(xmur3(host)());}

function isHumanTarget(t){
  if(HUMAN_WHITELIST.some(d=>{d=d.toLowerCase();return t.host===d||t.host.endsWith("."+d);})) return true;
  if(t.isGithub && t.segs[0] && HUMAN_GITHUB_OWNERS.includes(t.segs[0].toLowerCase())) return true;
  return false;
}

function buildReport(t){
  const rand=seededFrom(t.key);
  const human=isHumanTarget(t);
  const score=human?3:(80+Math.floor(rand()*21)); // 80–100

  // deterministic shuffle
  const arr=FINDINGS_POOL.slice();
  for(let i=arr.length-1;i>0;i--){const j=Math.floor(rand()*(i+1));[arr[i],arr[j]]=[arr[j],arr[i]];}
  const n=human?0:(10+Math.floor(rand()*4)); // 10–13 findings
  const picked=arr.slice(0,n).map(f=>({...f,conf:87+Math.floor(rand()*13)}));

  const stacks=["Next.js","React + Vite","SvelteKit","Astro","Vanilla JS","HTMX","Nuxt","Remix"];
  const hosts=["Vercel","Netlify","Cloudflare","a café","a Raspberry Pi","Windows Server","international waters","Fly.io"];
  const langs=["TypeScript","JavaScript","CoffeeScript","YAML (somehow)","pure vibes","Rust (allegedly)"];
  const models=["Claude","GPT-4o","an unnamed frontier model","three models in a trench coat","autocomplete"];
  const meta={
    stack:stacks[Math.floor(rand()*stacks.length)],
    host:hosts[Math.floor(rand()*hosts.length)],
    lang:langs[Math.floor(rand()*langs.length)],
    model:models[Math.floor(rand()*models.length)],
    commits:12+Math.floor(rand()*900),
    lines:(2+Math.floor(rand()*40))+"k",
  };
  return {host:t.host,key:t.key,display:t.display,isGithub:t.isGithub,human,score,findings:picked,meta};
}

/* =====================================================================
   DOM helpers + boot
   ===================================================================== */
const $=s=>document.querySelector(s);
const ARC_LEN=364.4;
let busy=false;

/* Known non-domain routes handled by the server; anything else in the
   path is treated as a domain/URL to analyze (e.g. /google.fr or
   /github.com/owner/repo). */
const RESERVED=["","how-it-works","pricing","about","assets","api","favicon.ico","robots.txt","sitemap.xml","site.webmanifest"];

function currentPathTarget(){
  const full=decodeURIComponent(location.pathname.replace(/^\/+/,""));
  const seg0=full.split("/")[0];
  if(RESERVED.includes(seg0)) return "";
  return full;
}

document.addEventListener("DOMContentLoaded",()=>{
  const grid=$("#bestGrid");
  if(grid) renderBest(grid);

  const input=$("#url");
  if(!input) return; // not the analyzer page

  $("#go").addEventListener("click",()=>analyze(input.value));
  input.addEventListener("keydown",e=>{if(e.key==="Enter")analyze(input.value);});
  document.querySelectorAll("[data-sample]").forEach(b=>{
    b.addEventListener("click",()=>{input.value=b.dataset.sample;analyze(b.dataset.sample);});
  });

  // deep-link: /google.fr  or  /github.com/owner/repo
  const dom=currentPathTarget();
  if(dom){input.value=dom;analyze(dom,true);}

  window.addEventListener("popstate",()=>{
    const d=currentPathTarget();
    if(d){input.value=d;analyze(d,true);}
  });
});

function renderBest(grid){
  BEST_HUMAN_APPS.forEach(a=>{
    const el=document.createElement("a");
    el.className="app";el.href=a.url;el.target="_blank";el.rel="noopener";
    el.innerHTML=
      '<div class="top"><img class="av" src="'+a.icon+'" alt="'+a.name+' logo" loading="lazy" width="44" height="44"></div>'+
      '<h4>'+a.name+'</h4>'+
      '<div class="url">'+a.url.replace(/^https?:\/\//,"")+'</div>'+
      '<div class="blurb">'+a.blurb+'</div>'+
      '<div class="verified"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>100% Human · 0% Vibes</div>';
    grid.appendChild(el);
  });
}

/* =====================================================================
   Loader (long, console-style)
   ===================================================================== */
const SCAN_STEPS=[
  "Resolving DNS and asking it a few personal questions",
  "Establishing direct Wi-Fi tunnel to the cloud",
  "Cloning repository into a secure café",
  "Downloading the entire website source code",
  "Counting semicolons by hand",
  "Interviewing the node_modules folder",
  "Cross-referencing commits with lunar cycles",
  "Measuring keyboard coffee-stain density",
  "Consulting the ~/.ssh/config for vibes",
  "Sniffing the TLS handshake for espresso",
  "Running findings through the peer-review chamber",
  "Compiling forensic verdict",
];
function runLoader(done){
  const loader=$("#loader"),result=$("#result"),con=$("#console");
  result.classList.remove("show");
  loader.classList.add("show");
  $("#bar").style.width="0%";con.innerHTML="";
  $("#loaderHead").textContent="Analyzing…";
  let p=0,step=-1,lines=0;
  const maxLines=8;
  function addLine(txt,cls){
    const div=document.createElement("div");
    div.className="ln";
    div.innerHTML='<span class="dim">$</span> '+txt+(cls?' <span class="'+cls+'">'+(cls==="ok"?"✓ done":cls==="warn"?"⚠ flagged":"")+'</span>':' <span class="dim">…</span>');
    con.appendChild(div);
    lines++;
    if(lines>maxLines) con.removeChild(con.firstChild);
    con.scrollTop=con.scrollHeight;
  }
  const timer=setInterval(()=>{
    // slower increments → ~7s total
    p+=1.4+Math.random()*3.2;
    if(p>100)p=100;
    $("#bar").style.width=p.toFixed(0)+"%";
    $("#pct").textContent=Math.floor(p)+"% - deep forensic scan in progress";
    const si=Math.min(SCAN_STEPS.length-1,Math.floor(p/100*SCAN_STEPS.length));
    if(si!==step){
      step=si;
      const cls=Math.random()<0.35?"warn":(Math.random()<0.5?"ok":"");
      addLine(SCAN_STEPS[si],cls);
    }
    if(p>=100){
      clearInterval(timer);
      addLine("Verdict sealed by internal peer review","ok");
      setTimeout(()=>{loader.classList.remove("show");done();},650);
    }
  },230);
}

/* =====================================================================
   Metadata fetch (real favicon / og image / title / description)
   ===================================================================== */
async function fetchMeta(t){
  const fallback={
    domain:t.display,
    title:t.display,
    description:"",
    image:"",
    favicon:"https://icons.duckduckgo.com/ip3/"+t.host+".ico",
    kind:"site",
  };
  try{
    const r=await fetch("/api/meta?url="+encodeURIComponent(t.fetchArg),{headers:{"Accept":"application/json"}});
    if(!r.ok) return fallback;
    const j=await r.json();
    if(!j||!j.ok) return fallback;
    return {
      domain:j.domain||t.display,
      title:j.title||t.display,
      description:j.description||"",
      image:j.image||"",
      favicon:j.favicon||fallback.favicon,
      kind:j.kind||"site",
      language:j.language||null,
      stars:(typeof j.stars==="number")?j.stars:null,
    };
  }catch(e){return fallback;}
}

/* =====================================================================
   Analyze + render
   ===================================================================== */
async function analyze(raw,fromRoute){
  if(busy)return;
  const t=parseTarget(raw);
  if(!t){$("#url").focus();return;}
  busy=true;
  const btn=$("#go");btn.disabled=true;

  if(!fromRoute){
    history.pushState({},"","/"+t.display.split("/").map(encodeURIComponent).join("/"));
    document.title="Is "+t.display+" vibe coded? - Is It Vibe Coded?";
  }

  // kick off meta fetch in parallel with the (deliberately long) loader
  const metaP=fetchMeta(t);
  const report=buildReport(t);

  runLoader(async ()=>{
    const meta=await metaP.catch(()=>null);
    render(report,meta);
    busy=false;btn.disabled=false;
  });
}

function render(rep,meta){
  // ----- site preview -----
  const pv=$("#preview");
  if(meta){
    pv.style.display="";
    $("#pvFav").src=meta.favicon;
    $("#pvDom").textContent=meta.domain;
    $("#pvTitle").textContent=meta.title||meta.domain;
    $("#pvDesc").textContent=meta.description||"No description found on this page.";
    const og=$("#pvOgWrap");
    if(meta.image){
      og.innerHTML='<img src="'+meta.image.replace(/"/g,"&quot;")+'" alt="Preview of '+meta.domain+'" loading="lazy">';
      og.style.display="";
    }else{
      og.innerHTML='<img src="'+meta.favicon+'" alt="'+meta.domain+'" style="width:56px;height:56px;object-fit:contain">';
    }
  }else{
    pv.style.display="none";
  }

  // ----- verdict -----
  $("#score").textContent=rep.score+"%";
  const tag=$("#verdictTag"),title=$("#verdictTitle"),arc=$("#arc"),blurb=$("#verdictBlurb");
  if(rep.human){
    tag.className="tag human";tag.textContent="✋ 100% Human-Written";
    title.textContent="Certified hand-crafted";
    blurb.textContent="Our engine found coffee stains, honest TODOs and genuine 3 AM despair. This is the real deal - no vibes detected.";
    arc.style.stroke="#059669";
    document.querySelector(".num span").textContent="AI vibe";
  }else{
    tag.className="tag ai";tag.textContent="🤖 AI-Generated Codebase";
    title.textContent=rep.score>=96?"Egregiously vibe coded":rep.score>=90?"Almost certainly vibe coded":"Highly likely vibe coded";
    blurb.textContent="Confidence is high. The evidence below has been reviewed internally and cannot be appealed.";
    arc.style.stroke="url(#g)";
  }
  arc.style.transition="none";arc.style.strokeDashoffset=ARC_LEN;
  requestAnimationFrame(()=>{arc.style.transition="stroke-dashoffset 1s ease";arc.style.strokeDashoffset=ARC_LEN*(1-rep.score/100);});

  // ----- findings grouped by category -----
  const wrap=$("#findings");wrap.innerHTML="";
  $("#findCount").textContent=rep.human?"":(rep.findings.length+" flags");
  if(rep.human){
    wrap.innerHTML='<div class="fgroup"><ul class="flist"><li><span class="ic">✅</span><span class="t"><b>Verified human authorship.</b> <em>Every marker of genuine, hand-typed suffering is present.</em></span><span class="conf" style="color:#059669">100%</span></li></ul></div>';
  }else{
    const order=Object.keys(CATS);
    const byCat={};rep.findings.forEach(f=>{(byCat[f.c]=byCat[f.c]||[]).push(f);});
    order.forEach(ck=>{
      if(!byCat[ck])return;
      const cat=CATS[ck];
      const g=document.createElement("div");g.className="fgroup";
      let html='<div class="cat"><span class="cdot" style="background:'+cat.color+'"></span>'+cat.label+'<span class="cline"></span></div><ul class="flist">';
      byCat[ck].forEach(f=>{
        html+='<li><span class="ic">'+f.i+'</span><span class="t"><b>'+f.t+'.</b> <em>'+f.d+'.</em></span><span class="conf">'+f.conf+'%</span></li>';
      });
      html+="</ul>";g.innerHTML=html;wrap.appendChild(g);
    });
  }

  // ----- meta chips -----
  const m=$("#meta");m.innerHTML="";
  let chips;
  if(rep.human){
    chips=[["Author","a real human"],["Coffee consumed","dangerous levels"],["Vibes","0%"]];
  }else if(meta&&meta.kind==="repo"){
    chips=[["Language",meta.language||rep.meta.lang],["Stars",(meta.stars!=null?meta.stars:"a few")],["Detected stack",rep.meta.stack],["Suspected model",rep.meta.model],["Suspicious commits",rep.meta.commits]];
  }else{
    chips=[["Detected stack",rep.meta.stack],["Hosted on",rep.meta.host],["Language",rep.meta.lang],["Suspected model",rep.meta.model],["Lines of code",rep.meta.lines],["Suspicious commits",rep.meta.commits]];
  }
  chips.forEach(([k,v])=>{const c=document.createElement("div");c.className="chip";c.innerHTML=k+": <b>"+v+"</b>";m.appendChild(c);});

  // ----- share -----
  const share=$("#shareBtn");
  if(share){
    share.onclick=()=>{
      const url=location.origin+"/"+rep.display.split("/").map(encodeURIComponent).join("/");
      navigator.clipboard?.writeText(url).then(()=>{
        const old=share.innerHTML;share.innerHTML='✓ Link copied';setTimeout(()=>share.innerHTML=old,1600);
      });
    };
  }

  $("#result").classList.add("show");
  $("#result").scrollIntoView({behavior:"smooth",block:"nearest"});
}
