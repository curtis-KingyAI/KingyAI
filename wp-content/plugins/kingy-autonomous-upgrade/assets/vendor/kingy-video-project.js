/* Portable project data. No network requests, credentials or media uploads. */
(function (scope) {
 'use strict';
 const schema='kingy-video-project-v2', key='kingyVideoProject.v2', legacyKey='kingyVideoJourney.v1', maxBytes=1000000;
 const fields=['title','subject','goal','audience','seconds','aspect','constraints','budget'];
 const tools=['script','shots','camera','prompt','commercial','budget'];
 const checks=['inputs','quality','costs','export'];
 const musicTracks={"calm-loop":{"title":"Calm Loop","creator":"wipics","file":"calm-loop.mp3","asset":"https://kingy.ai/wp-content/mu-plugins/kingy-video-journey/audio/calm-loop.mp3","source":"https://opengameart.org/content/calm-loop","license":"CC0-1.0","licenseUrl":"https://creativecommons.org/publicdomain/zero/1.0/","sha256":"1d7e386c079d4add6b3b1592054846e4977035f26844466e409467c6dc7bdbde"},"sherwood":{"title":"Sherwood","creator":"Chris Murphy (zesona)","file":"sherwood.ogg","asset":"https://kingy.ai/wp-content/mu-plugins/kingy-video-journey/audio/sherwood.ogg","source":"https://opengameart.org/content/sherwood","license":"CC0-1.0","licenseUrl":"https://creativecommons.org/publicdomain/zero/1.0/","sha256":"a7796c847682f5fd00041ffc0b0d71b67d95a41b4d6d914cd8e11bc37fe29a88"}};
 const positions=['top-left','top-center','top-right','middle-left','middle-center','middle-right','bottom-left','bottom-center','bottom-right'];
 const id=()=> 'p'+(globalThis.crypto?.randomUUID?.()||Date.now().toString(36)+Math.random().toString(36).slice(2));
 const object=x=>x!==null&&typeof x==='object'&&!Array.isArray(x);
 function str(x,max=2000){if(typeof x!=='string'||x.length>max)throw Error('A text field is missing or too long.');return x;}
 function number(x,min,max){if(typeof x!=='number'||!Number.isFinite(x)||x<min||x>max)throw Error('A number is outside the allowed range.');return x;}
 function url(x){str(x,4000);if(x&&!/^https?:\/\//i.test(x))throw Error('Asset links must start with https:// or http://.');if(x){const u=new URL(x);if(u.username||u.password)throw Error('Asset links must not contain credentials.');}return x;}
 function media(x){
  if(!object(x)||typeof x.id!=='string'||!/^[a-f0-9]{64}$/.test(x.id)||!['image/jpeg','image/png','image/webp','image/avif','video/mp4','video/webm','video/quicktime'].includes(x.type))throw Error('Invalid local media reference.');
  const m={id:x.id,name:str(x.name,250),type:x.type,size:number(x.size,1,64*1024*1024),width:number(x.width,1,32000000),height:number(x.height,1,32000000),duration:x.duration===null?null:number(x.duration,.001,86400)};
  if(!m.name||!Number.isInteger(m.size)||!Number.isInteger(m.width)||!Number.isInteger(m.height)||m.width*m.height>32e6||m.type.startsWith('video/')&&m.duration===null)throw Error('Invalid local media metadata.');return m;
 }
 function layout(x){
  if(x===undefined)return {position:'bottom-center',size:100};
  if(!object(x)||!positions.includes(x.position))throw Error('Choose a valid copy position.');
  const size=number(x.size,50,180);if(!Number.isInteger(size))throw Error('Copy size must be a whole percentage from 50 to 180.');
  return {position:x.position,size};
 }
 function framing(x){if(x===undefined)return {fit:'fit',x:50,y:50};if(!object(x)||!['fit','fill'].includes(x.fit))throw Error('Choose Fit or Fill for shot framing.');return {fit:x.fit,x:number(x.x,0,100),y:number(x.y,0,100)};}
 function frameStyle(x){const f=framing(x);return {objectFit:f.fit==='fill'?'cover':'contain',objectPosition:f.fit==='fill'?f.x+'% '+f.y+'%':'50% 50%'};}
 function frameRect(w,h,width,height,x){const f=framing(x),scale=(f.fit==='fill'?Math.max:Math.min)(width/w,height/h);return {x:(width-w*scale)*(f.fit==='fill'?f.x/100:.5),y:(height-h*scale)*(f.fit==='fill'?f.y/100:.5),width:w*scale,height:h*scale};}
 function sourceAudio(x){if(x===undefined)return {muted:true,volume:100};if(!object(x)||typeof x.muted!=='boolean')throw Error('Choose mute or keep original sound.');const volume=number(x.volume,0,100);if(!Number.isInteger(volume))throw Error('Original sound volume must be a whole percentage.');return {muted:x.muted,volume};}
 function music(x){
  if(x===undefined)return {track:'none',volume:25,fadeIn:1,fadeOut:1,source:null};
  if(!object(x)||!(x.track==='none'||Object.hasOwn(musicTracks,x.track)))throw Error('Choose an available licensed music track or No music.');
  const volume=number(x.volume,0,100);if(!Number.isInteger(volume))throw Error('Music volume must be a whole percentage.');
  return {track:x.track,volume,fadeIn:number(x.fadeIn,0,30),fadeOut:number(x.fadeOut,0,30),source:x.track==='none'?null:{...musicTracks[x.track]}};
 }
 function layoutStyle(value){
  const x=layout(value),[v,h]=x.position.split('-');
  return {left:h==='right'?'auto':h==='center'?'50%':'8%',right:h==='right'?'8%':'auto',top:v==='bottom'?'auto':v==='middle'?'50%':'15%',bottom:v==='bottom'?'15%':'auto',transform:'translate('+(h==='center'?'-50%':'0')+','+(v==='middle'?'-50%':'0')+')',textAlign:h,fontFamily:'Arial,Helvetica,sans-serif',fontSize:(6.5*x.size/100).toFixed(3)+'cqw'};
 }
 function shot(){return {id:id(),title:'Untitled shot',seconds:5,copy:'',noCopy:false,copyLayout:layout(),approvedCopy:null,pendingDraft:null,generationNotes:'',prompt:'',camera:'',asset:'',sourceIn:0,sourceAudio:sourceAudio(),notes:''};}
 function empty(){const s=shot();return {schema,id:id(),revision:0,updatedAt:'',brief:{title:'Untitled video project',subject:'',goal:'',audience:'',seconds:'5',aspect:'9:16',constraints:'',budget:''},shots:[s],activeShotId:s.id,music:music(),drafts:{},workingPrompt:'',attempts:[],checks:{},recipe:null};}
 function validate(x){
  if(!object(x)||x.schema!==schema)throw Error('Choose a Kingy video project v2 JSON backup or a Kingy video recipe v1.');
  const d=empty();d.id=str(x.id,100);d.revision=number(x.revision,0,1e12);d.updatedAt=str(x.updatedAt,60);
  if(!object(x.brief)||!object(x.drafts)||!object(x.checks))throw Error('Project brief or notes are missing.');
  fields.forEach(f=>d.brief[f]=str(x.brief[f]??'',2000));
  if(!['16:9','9:16','1:1','4:3','3:4','21:9'].includes(d.brief.aspect))throw Error('Unsupported project aspect ratio.');
  if(!Array.isArray(x.shots)||x.shots.length<1||x.shots.length>50)throw Error('A project needs 1–50 shots.');
  const ids=new Set();d.shots=x.shots.map(s=>{
   if(!object(s))throw Error('Invalid shot.');const n={id:str(s.id,100),title:str(s.title,250),seconds:number(s.seconds,0.1,600),copy:str(s.copy,4000),noCopy:s.noCopy===true,copyLayout:layout(s.copyLayout),approvedCopy:null,pendingDraft:null,generationNotes:str(s.generationNotes??'',50000),prompt:str(s.prompt,16000),camera:str(s.camera,16000),asset:url(s.asset),sourceIn:number(s.sourceIn,0,86400),sourceAudio:sourceAudio(s.sourceAudio),notes:str(s.notes,16000)};
   if(s.framing!==undefined)n.framing=framing(s.framing);
   if(s.localMedia!=null)n.localMedia=media(s.localMedia);
   if(n.noCopy&&n.copy.trim())throw Error('A shot marked without copy must have empty copy.');
   if(s.pendingDraft!=null){const pending=s.pendingDraft;if(!object(pending)||!['prompt','camera'].includes(pending.kind))throw Error('Invalid returned draft.');n.pendingDraft={kind:pending.kind,text:str(pending.text,16000),notes:str(pending.notes??'',50000),receivedAt:str(pending.receivedAt,60)};}
   if(ids.has(n.id)||!n.id)throw Error('Shot IDs must be unique.');ids.add(n.id);
   if(s.approvedCopy!==null){if(!object(s.approvedCopy)||s.approvedCopy.text!==n.copy||!n.copy.trim())throw Error('Approved copy does not match the current text. Review it again.');n.approvedCopy={text:n.copy,at:str(s.approvedCopy.at,60)};}
   return n;
  });
  const localRefs=new Map();d.shots.forEach(s=>{if(!s.localMedia)return;const m=s.localMedia,signature=JSON.stringify([m.type,m.size,m.width,m.height,m.duration]);if(localRefs.has(m.id)&&localRefs.get(m.id)!==signature)throw Error('Conflicting metadata for the same local file.');localRefs.set(m.id,signature);});
  d.music=music(x.music);
  d.activeShotId=ids.has(x.activeShotId)?x.activeShotId:d.shots[0].id;
  tools.forEach(t=>{if(x.drafts[t]!==undefined)d.drafts[t]=str(x.drafts[t],50000);});d.workingPrompt=str(x.workingPrompt??'',16000);
  if(!Array.isArray(x.attempts)||x.attempts.length>50)throw Error('At most 50 attempt records are supported.');
  d.attempts=x.attempts.map(a=>({route:str(a.route,250),file:str(a.file,250),notes:str(a.notes,1800),cost:a.cost===null?null:number(a.cost,0,100000),minutes:a.minutes===null?null:number(a.minutes,0,100000),verdict:['unreviewed','usable','retry'].includes(a.verdict)?a.verdict:'unreviewed',recordedAt:str(a.recordedAt,60)}));
  checks.forEach(c=>d.checks[c]=x.checks[c]===true);
  // Retain the source specification for provenance; never execute or embed its content.
  if(x.recipe!==null&&x.recipe!==undefined){if(!object(x.recipe)||JSON.stringify(x.recipe).length>100000)throw Error('Source recipe is too large.');d.recipe=JSON.parse(JSON.stringify(x.recipe));}
  d.brief.seconds=String(total(d));return d;
 }
 function total(d){return Math.round(d.shots.reduce((n,s)=>n+s.seconds,0)*100)/100;}
 function editCopy(s,text){s.copy=text;if(text.trim())s.noCopy=false;if(s.approvedCopy?.text!==text)s.approvedCopy=null;}
 function migrate(old){
  if(!object(old)||old.version!==1)throw Error('Unsupported legacy project.');
  const d=empty();fields.forEach(f=>{if(typeof old.brief?.[f]==='string')d.brief[f]=old.brief[f].slice(0,2000);});
  d.shots[0].title='Recovered first shot';d.shots[0].seconds=Math.max(.1,Math.min(600,Number(d.brief.seconds)||5));
  d.workingPrompt=typeof old.workingPrompt==='string'?old.workingPrompt.slice(0,16000):'';d.shots[0].prompt=d.workingPrompt;
  tools.forEach(t=>{if(typeof old.drafts?.[t]==='string')d.drafts[t]=old.drafts[t].slice(0,50000);});
  d.attempts=Array.isArray(old.attempts)?old.attempts:[];d.checks=old.checks||{};return validate(d);
 }
 function fromRecipe(r){
  if(!object(r)||r.schema!=='kingy-video-recipe-v1'||!Array.isArray(r.timeline)||r.timeline.length<1||r.timeline.length>50)throw Error('Invalid video recipe.');
  const d=empty();d.brief.title=str(r.title,2000);d.brief.subject=str(r.subject||(typeof r.product_truth==='string'?r.product_truth:'')||'Describe the subject shown in the linked source asset.',2000);d.brief.goal=typeof r.brief==='string'?r.brief:JSON.stringify(r.brief);d.brief.constraints=typeof r.product_truth==='string'?r.product_truth:JSON.stringify(r.product_truth||'');d.brief.aspect=r.export?.width>r.export?.height?'16:9':'9:16';
  let end=0;d.shots=r.timeline.map((t,i)=>{if(t.start_seconds!==end)throw Error('Recipe timeline must be contiguous and start at zero.');end=t.end_seconds;const s=shot();s.title=t.title||(['Opening','Detail','End card'][i]||'Shot '+(i+1));s.seconds=t.end_seconds-t.start_seconds;s.copy=t.copy||'';s.noCopy=t.no_copy===true;s.generationNotes=t.recorded_request?'Historical request (review for a new generation):\n'+JSON.stringify(t.recorded_request,null,2):'';s.asset=t.asset||'';s.sourceIn=t.source_in_seconds||0;s.prompt=t.recorded_request?.promptText||'';s.notes=t.treatment||'Retained example. Review framing and copy for your own brief.';return s;});
  d.activeShotId=d.shots[0].id;d.recipe=r;return validate(d);
 }
 function parse(raw){if(typeof raw!=='string'||new TextEncoder().encode(raw).length>maxBytes)throw Error('Choose a JSON file smaller than 1 MB.');const x=JSON.parse(raw);return x.schema==='kingy-video-recipe-v1'?fromRecipe(x):validate(x);}
 function read(storage,session){let token=null;try{token=storage.getItem(key);if(token!==null)return {data:parse(token),token,error:null,migrated:false};const old=session?.getItem(legacyKey);return {data:old?migrate(JSON.parse(old)):empty(),token:null,error:null,migrated:!!old};}catch(e){return {data:empty(),token,error:'Saved data could not be read. It has not been overwritten. '+e.message,migrated:false,corrupt:token!==null};}}
 function write(storage,d,token){if(storage.getItem(key)!==token)throw Error('This project changed in another tab. Download this page’s JSON backup, then reload the saved project.');const next=validate(d);next.revision++;next.updatedAt=new Date().toISOString();const raw=JSON.stringify(next);if(new TextEncoder().encode(raw).length>maxBytes)throw Error('Project exceeds 1 MB. Shorten notes before saving.');storage.setItem(key,raw);return {data:next,token:raw};}
 function filename(d,kind='project'){const title=d.brief.title.normalize('NFKD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'').slice(0,70)||'kingy-video';return title+'-'+kind+'-'+new Date().toISOString().slice(0,10)+(kind==='edit-guide'?'.md':'.json');}
 const api={framing,frameStyle,frameRect,media,sourceAudio,music,musicTracks,layout,layoutStyle,positions,filename,schema,key,legacyKey,maxBytes,fields,tools,checks,id,empty,shot,validate,total,editCopy,migrate,fromRecipe,parse,read,write};
 if(typeof module!=='undefined'&&module.exports)module.exports=api;else scope.KingyVideoProject=api;
}(typeof window!=='undefined'?window:globalThis));
