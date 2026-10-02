import {aggregateCost} from './vendor/kingy-workbench-cost.mjs';

export const SCHEMA=1;
const uuid=()=>globalThis.crypto.randomUUID();
const text=(v,max=3000)=>{if(typeof v!=='string'||v.length>max)throw Error('Invalid or oversized text.');return v;};
const number=(v,min,max)=>{if(typeof v!=='number'||!Number.isFinite(v)||v<min||v>max)throw Error('A numeric value is outside its allowed range.');return v;};
const money=(v)=>v===null?null:number(v,0,1000000);
const product=v=>{if(typeof v!=='string'||v!==''&&!/^wp:kingy_ai_(?:tool|model):[1-9][0-9]{0,9}$/.test(v))throw Error('Invalid product identity.');return v;};
export const briefSignature=p=>JSON.stringify(p.brief);
export const shotSignature=p=>JSON.stringify(p.shots);
const own=(v)=>v&&typeof v==='object'&&!Array.isArray(v);
export function blankProject(){return {schemaVersion:SCHEMA,id:uuid(),name:'Untitled commercial',serverRevision:0,
 brief:{product:'',audience:'',message:'',format:'16:9',duration:20,style:'Clean product demonstration',budget:0,currency:'USD',productId:'',referenceNote:''},
 outline:'',shots:[],prompts:[],outlineBrief:null,promptShots:null,promptBrief:null,
 budget:{price:null,unit:'second',attemptsPerShot:3,usableFraction:0.5,reviewMinutesPerAttempt:2,laborRate:0,evidenceDate:'',sourceUrl:'',rateEventKey:''},referenceMetadata:null};}
export function stale(p){return {outline:p.outlineBrief!==briefSignature(p),prompts:p.promptShots!==shotSignature(p)||p.promptBrief!==briefSignature(p)};}
export function prepareOutline(p){
 if(!p.brief.product.trim()||!p.brief.audience.trim()||!p.brief.message.trim())throw Error('Enter the product, audience and intended message first.');
 const d=p.brief.duration, durations=[Math.max(1,Math.round(d*.25)),Math.max(1,Math.round(d*.5))];durations.push(d-durations[0]-durations[1]);
 p.outline=`Show ${p.brief.audience} a specific problem, demonstrate ${p.brief.product}, then close with ${p.brief.message}. Style: ${p.brief.style}. Verify every product claim before publication.`;
 p.shots=[{id:uuid(),purpose:'Establish the problem',description:`Show the context in which ${p.brief.audience} needs ${p.brief.product}.`,seconds:durations[0],framing:'Medium',camera:'Locked',motion:'Subtle subject movement',route:'generic'},
 {id:uuid(),purpose:'Demonstrate the product',description:`Show ${p.brief.product} in use. Show a real supported benefit related to: ${p.brief.message}.`,seconds:durations[1],framing:'Close-up',camera:'Slow push-in',motion:'One controlled action',route:'generic'},
 {id:uuid(),purpose:'Close with the message',description:`Clean product hero composition. Add “${p.brief.message}” as an editable overlay in the editor.`,seconds:durations[2],framing:'Wide',camera:'Locked',motion:'Minimal',route:'generic'}];
 p.outlineBrief=briefSignature(p);return p;
}
export const ROUTES={generic:'General image-to-video template',artlist:'Artlist Studio shot-direction template',invideo:'InVideo scene-direction template'};
export function preparePrompts(p){
 if(stale(p).outline)throw Error('Revise or regenerate the outline and shots for the current brief first.');
 if(!p.shots.length)throw Error('Prepare a shot list first.');
 p.prompts=p.shots.map(s=>({shotId:s.id,route:s.route,text:`Planning template — ${ROUTES[s.route]}. No inference performed.\nProduct: ${p.brief.product}\nAudience: ${p.brief.audience}\nIntended message: ${p.brief.message}\nShot: ${s.description}\nFraming: ${s.framing}. Camera: ${s.camera}. Subject motion: ${s.motion}.\nDuration target: ${s.seconds}s. Aspect: ${p.brief.format}. Style: ${p.brief.style}.\nPreserve product shape and identity. Do not add unsupported capabilities, logos or wording. Add final text as editable overlays in post-production.\nReference: ${p.brief.referenceNote||'No reference direction supplied.'}\nConfirm this tool/account supports the chosen duration, aspect, reference handling and commercial rights before generation.`}));
 p.promptShots=shotSignature(p);p.promptBrief=briefSignature(p);return p;
}
export function estimate(p){
 const b=p.budget,totalSeconds=p.shots.reduce((n,s)=>n+s.seconds,0),attempts=p.shots.length*b.attemptsPerShot;
 const estimatedToolSpend=b.price===null?null:b.price*b.attemptsPerShot*(b.unit==='second'?totalSeconds:p.shots.length);
 // Reuse the existing Kingy workbench aggregation for comparable planning attempts.
 // No acceptance decision or benchmark is inferred: these are explicit scenario assumptions.
 const rows=[];for(const s of p.shots)for(let i=0;i<b.attemptsPerShot;i++)rows.push({id:`${s.id}:${i}`,accepted:false,toolCostUSD:b.price===null?null:b.price*(b.unit==='second'?s.seconds:1),humanMinutes:b.reviewMinutesPerAttempt,elapsedSeconds:null});
 const aggregate=aggregateCost(rows,{laborRateUSD:b.laborRate});
 const expectedUsableOutputs=attempts*b.usableFraction;
 return {currency:p.brief.currency,totalSeconds,plannedAttempts:attempts,estimatedToolSpend:rows.length?aggregate.toolCostUSD:estimatedToolSpend,
 reviewMinutes:rows.length?aggregate.humanMinutes:0,estimatedLaborSpend:rows.length?aggregate.laborCostUSD:0,
 estimatedCombinedSpend:rows.length?aggregate.combinedCostUSD:0,expectedUsableOutputs,
 estimatedToolCostPerUsableOutput:expectedUsableOutputs&&estimatedToolSpend!==null?estimatedToolSpend/expectedUsableOutputs:null,
 assumptions:'Planning estimate, not measured acceptance. Subscriptions, tax, FX, repair, editing and unused credits are excluded. The usable fraction is your assumption.',
 priceEvidenceDate:b.evidenceDate||null,priceSource:b.sourceUrl||null,rateEventKey:b.rateEventKey||null,
 exceedsBudget:estimatedToolSpend!==null&&p.brief.budget>0&&(estimatedToolSpend+(aggregate.laborCostUSD||0))>p.brief.budget};
}
function rejectPrototype(value){if(value&&typeof value==='object'){for(const k of Object.keys(value)){if(['__proto__','prototype','constructor'].includes(k))throw Error('Unsafe import property.');rejectPrototype(value[k]);}}}
export function importProject(input){
 if(typeof input==='string'){if(input.length>200000)throw Error('Project must be at most 200 KB.');input=JSON.parse(input);}
 rejectPrototype(input);if(!own(input)||input.schemaVersion!==SCHEMA)throw Error('Unsupported project schema.');
 const p=blankProject();p.id=text(input.id,64);if(!/^[a-zA-Z0-9-]{16,64}$/.test(p.id))throw Error('Invalid project ID.');p.name=text(input.name,120);
 const b=input.brief;if(!own(b))throw Error('Brief required.');
 for(const k of ['product','audience','message','style','referenceNote'])p.brief[k]=text(b[k]);
 p.brief.format=text(b.format,10);if(!['16:9','9:16','1:1'].includes(p.brief.format))throw Error('Invalid aspect ratio.');
 p.brief.duration=number(b.duration,6,180);p.brief.budget=number(b.budget,0,1000000);p.brief.currency=text(b.currency,3);if(!['USD','CAD','EUR','GBP'].includes(p.brief.currency))throw Error('Unsupported currency.');p.brief.productId=product(b.productId);
 p.outline=text(input.outline,10000);if(!Array.isArray(input.shots)||input.shots.length>40)throw Error('Use at most 40 shots.');
 const ids=new Set();p.shots=input.shots.map(s=>{if(!own(s))throw Error('Invalid shot.');const o={id:text(s.id,64),seconds:number(s.seconds,1,180)};if(!/^[a-zA-Z0-9-]{16,64}$/.test(o.id)||ids.has(o.id))throw Error('Duplicate or invalid shot ID.');ids.add(o.id);for(const k of ['purpose','description','framing','camera','motion','route'])o[k]=text(s[k]);if(!Object.hasOwn(ROUTES,o.route))throw Error('Unsupported prompt route.');return o;});
 const promptIds=new Set();if(!Array.isArray(input.prompts)||input.prompts.length>40)throw Error('Invalid prompts.');p.prompts=input.prompts.map(s=>{if(!own(s)||!ids.has(s.shotId)||promptIds.has(s.shotId)||!Object.hasOwn(ROUTES,s.route))throw Error('Prompt shot relationship missing or duplicated.');promptIds.add(s.shotId);return {shotId:s.shotId,route:text(s.route,40),text:text(s.text,12000)};});
 for(const k of ['outlineBrief','promptShots','promptBrief'])p[k]=input[k]===null?null:text(input[k],60000);
 const budget=input.budget;if(!own(budget))throw Error('Budget required.');
 p.budget={price:money(budget.price),unit:text(budget.unit,20),attemptsPerShot:number(budget.attemptsPerShot,1,20),usableFraction:number(budget.usableFraction,0.01,1),reviewMinutesPerAttempt:number(budget.reviewMinutesPerAttempt,0,1440),laborRate:number(budget.laborRate,0,10000),evidenceDate:text(budget.evidenceDate,30),sourceUrl:text(budget.sourceUrl,2048),rateEventKey:text(budget.rateEventKey,191)};
 if(!Number.isInteger(p.budget.attemptsPerShot)||!['second','generation'].includes(p.budget.unit))throw Error('Invalid attempt count or price unit.');
 if(p.budget.evidenceDate && !Number.isFinite(Date.parse(p.budget.evidenceDate)))throw Error('Invalid price evidence date.');
 if(p.budget.sourceUrl){const u=new URL(p.budget.sourceUrl);if(u.protocol!=='https:'||u.username||u.password)throw Error('Unsafe source URL.');}
 p.serverRevision=Number.isInteger(input.serverRevision)&&input.serverRevision>=0?input.serverRevision:0;
 if(input.referenceMetadata!==null){const r=input.referenceMetadata;if(!own(r))throw Error('Invalid reference metadata.');p.referenceMetadata={name:text(r.name,150),width:number(r.width,1,12000),height:number(r.height,1,12000)};}
 return p;
}
export function duplicateProject(p){const c=importProject(JSON.stringify(p));c.id=uuid();c.name=(c.name+' copy').slice(0,120);c.serverRevision=0;return c;}
export function applyPrice(p,record,now=Date.now()){
 const r=record.price;if(!r||!['second','generation'].includes(r.unit)||r.currency!==p.brief.currency)throw Error('No compatible generation price is verified. Enter your own estimate.');
 const date=Date.parse(r.verified_at);if(!Number.isFinite(date)||date>now||now-date>7*86400000)throw Error('This generation price needs a new source review.');
 p.budget.price=money(r.amount);p.budget.unit=r.unit;p.budget.evidenceDate=r.verified_at;p.budget.sourceUrl=r.source_url;p.budget.rateEventKey=r.event_key;return p;
}
export function packMarkdown(p){
 const e=estimate(p),s=stale(p);if(s.outline||s.prompts)throw Error('Review the stale outline and prompts before exporting the production pack.');
 if(!p.outline.trim()||!p.shots.length||p.prompts.length!==p.shots.length||p.prompts.some(prompt=>!prompt.text.trim()))throw Error('Complete the outline, shots and prompt templates before exporting.');
 const line=value=>String(value).replaceAll('\r','');
 return `# ${line(p.name)}\n\nPlanning templates — no remote generation has been performed.\n\n## Brief\n${Object.entries(p.brief).map(([k,v])=>`- ${k}: ${line(v)}`).join('\n')}\n\nReference image is not included; attach your authorized original separately.\n\n## Creative outline\n${line(p.outline)}\n\n## Shot list\n${p.shots.map((s,i)=>`### ${i+1}. ${line(s.purpose)} (${s.seconds}s)\n${line(s.description)}\nFraming: ${line(s.framing)}. Camera: ${line(s.camera)}. Motion: ${line(s.motion)}.`).join('\n\n')}\n\n## Prompt templates\n${p.prompts.map((r,i)=>`### Shot ${i+1}\n${line(r.text)}`).join('\n\n')}\n\n## Budget estimate\n${JSON.stringify(e,null,2)}\n\n## Editable assumptions\n${JSON.stringify(p.budget,null,2)}\n\n## Sources and next checks\n${p.budget.sourceUrl||'No verified generation price supplied; the amount is a user estimate or unknown.'}\nEvidence date: ${p.budget.evidenceDate||'not supplied'}\nProduct record: ${p.brief.productId||'not selected'}\nCamera guide: https://kingy.ai/ai-camera-simulator/\nProduction recipes: https://kingy.ai/make-this/\nConfirm model/account access, exact settings, commercial rights and every product claim.\n`.replace(/[ \t]+$/gm,'');
}

export function validateImageHeader(bytes,mime){
 const b=new Uint8Array(bytes);return mime==='image/png'?b.length>8&&[137,80,78,71,13,10,26,10].every((v,i)=>b[i]===v):mime==='image/jpeg'?b.length>3&&b[0]===255&&b[1]===216&&b[2]===255:mime==='image/webp'?b.length>12&&String.fromCharCode(...b.slice(0,4))==='RIFF'&&String.fromCharCode(...b.slice(8,12))==='WEBP':false;
}
