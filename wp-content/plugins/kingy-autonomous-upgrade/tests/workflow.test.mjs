import test from 'node:test';
import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {blankProject,prepareOutline,preparePrompts,stale,importProject,duplicateProject,estimate,packMarkdown,applyPrice,validateImageHeader} from '../assets/workflow.mjs';
import {toVideoProject,importVideoDirections} from '../assets/video-bridge.mjs';
const M=createRequire(import.meta.url)('../assets/vendor/kingy-video-project.js');
const example=()=>{const p=blankProject();p.name='Bottle commercial';Object.assign(p.brief,{product:'Reusable steel bottle',audience:'Commuters',message:'Bring your water, reuse your bottle',duration:20,budget:50});return p;};

test('complete, backtrack, restore and revise a realistic commercial',()=>{
 const p=example();prepareOutline(p);assert.equal(p.shots.reduce((n,s)=>n+s.seconds,0),20);preparePrompts(p);
 assert.deepEqual(stale(p),{outline:false,prompts:false});assert.match(packMarkdown(p),/Reusable steel bottle/);
 const restored=importProject(JSON.stringify(p));assert.deepEqual(restored,p);
 p.brief.audience='Cyclists';assert.equal(stale(p).outline,true);assert.throws(()=>packMarkdown(p),/stale/);
 prepareOutline(p);preparePrompts(p);p.shots[0].camera='Slow lateral truck';assert.equal(stale(p).prompts,true);
 preparePrompts(p);assert.match(p.prompts[0].text,/Slow lateral truck/);assert.match(packMarkdown(p),/Cyclists/);
 const copy=duplicateProject(p);assert.notEqual(copy.id,p.id);assert.equal(copy.serverRevision,0);
});
test('budget carries price units, attempts and unknown costs without pretending acceptance',()=>{
 const p=example();prepareOutline(p);assert.equal(estimate(p).estimatedToolSpend,null);
 Object.assign(p.budget,{price:.2,attemptsPerShot:3,usableFraction:.5,laborRate:30});
 const e=estimate(p);assert.equal(e.plannedAttempts,9);assert.equal(e.estimatedToolSpend,12);assert.equal(e.reviewMinutes,18);assert.equal(e.estimatedCombinedSpend,21);assert.equal(e.expectedUsableOutputs,4.5);
 p.budget.unit='generation';assert.ok(Math.abs(estimate(p).estimatedToolSpend-1.8)<1e-9);
 p.budget.price=0;assert.equal(estimate(p).estimatedToolSpend,0);
});
test('verified price needs compatible currency, unit and a fresh independent date',()=>{
 const p=example(),r={price:{amount:.12,currency:'USD',unit:'second',verified_at:'2026-10-01T12:00:00Z',source_url:'https://example.org/pricing',event_key:'example:price:v2'}};
 applyPrice(p,r,Date.parse('2026-10-01T13:00:00Z'));assert.equal(p.budget.price,.12);assert.equal(p.budget.rateEventKey,r.price.event_key);
 assert.throws(()=>applyPrice(p,r,Date.parse('2026-10-09T13:00:00Z')),/review/);p.brief.currency='CAD';assert.throws(()=>applyPrice(p,r),/compatible/);
});
test('malformed imports, prototype keys, duplicate shot IDs, unsafe URLs and large files fail',()=>{
 const p=example();prepareOutline(p);preparePrompts(p);
 const bad=structuredClone(p);bad.shots[1].id=bad.shots[0].id;assert.throws(()=>importProject(bad),/Duplicate/);
 assert.throws(()=>importProject('{"__proto__":{}}'),/Unsafe/);assert.throws(()=>importProject(' '.repeat(200001)),/200 KB/);
 const urls=structuredClone(p);urls.budget.sourceUrl='https://user:secret@example.org/';assert.throws(()=>importProject(urls),/Unsafe/);
 const prompts=structuredClone(p);prompts.prompts[1].shotId=prompts.prompts[0].shotId;assert.throws(()=>importProject(prompts),/duplicated/);
 assert.equal(validateImageHeader(new Uint8Array([137,80,78,71,13,10,26,10,0]).buffer,'image/png'),true);
 assert.equal(validateImageHeader(new TextEncoder().encode('<svg/>').buffer,'image/png'),false);
});
test('reuse existing camera workspace with matching project and stable shot IDs',()=>{
 const p=example();prepareOutline(p);preparePrompts(p);const native=toVideoProject(p,M,p.shots[1].id);
 assert.equal(native.brief.subject,p.brief.product);assert.equal(native.activeShotId,p.shots[1].id);assert.equal(native.schema,'kingy-video-project-v2');
 native.shots[1].pendingDraft={kind:'camera',text:'Orbit 20 degrees over 10 seconds, eye-level 50mm lens.',notes:'Planning preview only',receivedAt:'2026-10-01T12:00:00Z'};
 assert.equal(importVideoDirections(p,native,M),1);assert.match(p.shots[1].camera,/50mm/);assert.equal(stale(p).prompts,true);
 native.id='other-project';assert.throws(()=>importVideoDirections(p,native,M),/another project/);
});
