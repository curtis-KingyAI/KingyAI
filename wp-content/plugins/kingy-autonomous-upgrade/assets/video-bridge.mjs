/** Reuse the existing Kingy video-project model and its conflict-aware handoff. */
export function toVideoProject(project,model,shotId){
 const d=model.empty();d.id=project.id;d.brief={title:project.name.slice(0,2000),subject:project.brief.product.slice(0,2000),goal:project.brief.message.slice(0,2000),audience:project.brief.audience.slice(0,2000),seconds:String(project.brief.duration),aspect:project.brief.format,constraints:[project.brief.style,project.brief.referenceNote].join('\n').slice(0,2000),budget:project.brief.budget+' '+project.brief.currency};
 d.shots=project.shots.map(s=>({...model.shot(),id:s.id,title:s.purpose.slice(0,250),seconds:s.seconds,prompt:project.prompts.find(p=>p.shotId===s.id)?.text||'',camera:s.camera,notes:[s.description,'Framing: '+s.framing,'Motion: '+s.motion].join('\n')}));
 d.activeShotId=shotId||d.shots[0]?.id;d.recipe={schema:'kingy-commercial-planning-reference',projectId:project.id};return model.validate(d);
}
export function importVideoDirections(project,native,model){
 const d=model.validate(native);if(d.id!==project.id||d.recipe?.projectId!==project.id)throw Error('The saved video workspace belongs to another project.');let count=0;
 for(const s of project.shots){const n=d.shots.find(n=>n.id===s.id);if(!n)continue;
  if(n.pendingDraft?.kind==='camera'){s.camera=n.pendingDraft.text;count++;}
  else if(n.camera&&n.camera!==s.camera){s.camera=n.camera;count++;}
  const prompt=project.prompts.find(p=>p.shotId===s.id);
  if(n.pendingDraft?.kind==='prompt'&&prompt){prompt.text=n.pendingDraft.text;count++;}
  else if(n.prompt&&prompt&&n.prompt!==prompt.text){prompt.text=n.prompt;count++;}
 }
 if(!count)throw Error('No new camera or prompt direction is available for this project.');return count;
}
