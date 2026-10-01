/** Aggregate only comparable task/model attempts. Unknown is not zero. */
export function aggregateCost(attempts,{laborRateUSD=null}={}) {
 if(!Array.isArray(attempts)) throw new TypeError('Attempts must be an array');
 if(laborRateUSD!==null && (!Number.isFinite(laborRateUSD)||laborRateUSD<0)) throw new RangeError('Labor rate must be nonnegative or null');
 const ids=new Set();
 for(const a of attempts){
  if(!a || typeof a.id!=='string' || !a.id || ids.has(a.id)) throw new TypeError('Attempt ids must be unique nonempty strings');
  ids.add(a.id);
  if(typeof a.accepted!=='boolean') throw new TypeError('Every attempt needs an acceptance decision');
  for(const key of ['toolCostUSD','humanMinutes','elapsedSeconds'])if(a[key]!==null && (!Number.isFinite(a[key])||a[key]<0))throw new RangeError(key+' must be nonnegative or null');
 }
 const sum=key=>attempts.length && attempts.every(a=>a[key]!==null)?attempts.reduce((s,a)=>s+a[key],0):null;
 const successes=attempts.filter(a=>a.accepted).length;
 const toolCostUSD=sum('toolCostUSD'),humanMinutes=sum('humanMinutes'),elapsedSeconds=sum('elapsedSeconds');
 const laborCostUSD=humanMinutes!==null && laborRateUSD!==null ?humanMinutes/60*laborRateUSD:null;
 const combinedCostUSD=toolCostUSD!==null && laborCostUSD!==null?toolCostUSD+laborCostUSD:null;
 return {attempts:attempts.length,accepted:successes,failed:attempts.length-successes,successRate:attempts.length?successes/attempts.length:null,toolCostUSD,humanMinutes,elapsedSeconds,laborRateUSD,laborCostUSD,combinedCostUSD,costPerAcceptedUSD:successes && toolCostUSD!==null?toolCostUSD/successes:null,combinedCostPerAcceptedUSD:successes && combinedCostUSD!==null?combinedCostUSD/successes:null,unknownToolCharges:attempts.filter(a=>a.toolCostUSD===null).length};
}
