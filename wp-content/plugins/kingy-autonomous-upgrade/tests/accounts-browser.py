"""Actual local WordPress cookies, REST nonces and account storage; no auth/catalog mocks."""
import json, pathlib, subprocess
from playwright.sync_api import sync_playwright, expect

BASE='http://127.0.0.1:8081'
OUT=pathlib.Path('/workspace/KingyAI/docs/autonomous-upgrade/evidence')
COOKIE_PATH=pathlib.Path('/workspace/kingy-upgrade/staging/wordpress/kau-browser-cookies.json')
docker=['env','-u','DOCKER_HOST','-u','DOCKER_CONTEXT','-u','DOCKER_TLS','-u','DOCKER_TLS_VERIFY','-u','DOCKER_CERT_PATH','docker','--host=unix:///var/run/docker.sock','exec','kingy-upgrade-wp','wp']
prepared=subprocess.run(docker+['eval-file','/var/www/html/wp-content/plugins/kingy-autonomous-upgrade/tests/browser-accounts.php','--path=/var/www/html'],capture_output=True,text=True)
if prepared.returncode: raise RuntimeError('Local fixture account preparation failed; credential output withheld.')
cookies=json.loads(COOKIE_PATH.read_text());checks=[]
def request(page,path,method='GET',body=None,nonce=True):
    return page.evaluate('''async ({path,method,body,nonce})=>{
      const headers={'Content-Type':'application/json'};if(nonce)headers['X-WP-Nonce']=window.KingyUpgrade.nonce;
      const r=await fetch(window.KingyUpgrade.api+path,{method,headers,credentials:'same-origin',...(body===null?{}:{body:JSON.stringify(body)})});
      return {status:r.status,body:await r.json()};
    }''',{'path':path,'method':method,'body':body,'nonce':nonce})
def workflow(page):
    page.goto(BASE+'/?pagename=commercial-staging');root=page.locator('[data-kau="workflow"]');expect(root.locator('[data-action="save-server"]')).to_be_visible();return root
def fill(root,name):
    root.locator('#kau-name').fill(name);root.locator('#kau-name').blur()
    for field,value in [('product','Steel bottle'),('audience','Commuters'),('message','Refill every day')]:
        root.locator('[data-field="brief.'+field+'"]').fill(value);root.locator('[data-field="brief.'+field+'"]').blur()

try:
 with sync_playwright() as pw:
    browser=pw.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
    a=browser.new_context(accept_downloads=True);a.add_cookies([cookies['kau-browser-a']]);page=a.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    root=workflow(page);fill(root,'Native account commercial');root.locator('[data-action="save-server"]').click();expect(root.locator('.kau-status')).to_contain_text('saved to your authenticated account')
    records=request(page,'projects');assert records['status']==200 and len(records['body'])==1
    record=records['body'][0];project_id=record['id'];project=record['payload'];assert record['revision']==1
    assert request(page,'projects','GET',nonce=False)['status']==401
    checks.append('Actual native cookie/REST nonce saves project; missing nonce cannot read private projects')
    b=browser.new_context(accept_downloads=True);b.add_cookies([cookies['kau-browser-b']]);other=b.new_page();other_root=workflow(other)
    assert request(other,'projects')['body']==[]
    assert request(other,'projects/'+project_id,'PUT',project)['status']==404
    assert request(other,'projects/'+project_id,'DELETE')['status']==404
    fill(other_root,'Other account commercial');other_root.locator('[data-action="save-server"]').click();expect(other_root.locator('.kau-status')).to_contain_text('saved to your authenticated account')
    checks.append('Second authenticated user cannot read, overwrite or delete first-user project')
    second=browser.new_context(viewport={'width':390,'height':900},accept_downloads=True);second.add_cookies([cookies['kau-browser-a']]);cross=second.new_page();cross_root=workflow(cross)
    cross_root.locator('[data-action="load-server"]').click();cross_root.locator('[data-load-index="0"]').click();expect(cross_root.locator('#kau-name')).to_have_value('Native account commercial')
    cross_root.locator('[data-field="brief.message"]').fill('Refill for your commute');cross_root.locator('[data-field="brief.message"]').blur();cross_root.locator('[data-action="save-server"]').click();expect(cross_root.locator('.kau-status')).to_contain_text('saved to your authenticated account')
    root.locator('[data-action="save-server"]').click();expect(root.locator('.kau-status')).to_contain_text('changed elsewhere')
    root.locator('[data-action="load-server"]').click();root.locator('[data-load-index="0"]').click();expect(root.locator('[data-field="brief.message"]')).to_have_value('Refill for your commute')
    checks.append('Cross-device restore/update works on mobile; stale editor recovers from real 409 conflict')
    cross_root.locator('[data-action="duplicate"]').click();cross_root.locator('[data-action="save-server"]').click();expect(cross_root.locator('.kau-status')).to_contain_text('saved to your authenticated account')
    cross_root.locator('#kau-save-device').check();cross_root.locator('[data-action="save-stack"]').click()
    cross.goto(BASE+'/?pagename=my-ai-stack-staging');stack=cross.locator('[data-kau="stack"]');stack.locator('[data-action="sync"]').click();expect(stack.locator('.kau-status')).to_contain_text('saved to your account')
    page.goto(BASE+'/?pagename=my-ai-stack-staging');account_stack=page.locator('[data-kau="stack"]');account_stack.locator('[data-action="restore"]').click();expect(account_stack).to_contain_text('Native account commercial copy')
    assert request(other,'stack')['body']['projects']==[]
    checks.append('Saved stack/projects restore in another native session without exposing them to another user')
    account_stack.locator('[data-action="digest"]').click();expect(account_stack.locator('.kau-status')).to_contain_text('Check the explicit')
    account_stack.locator('#kau-digest-opt-in').check();account_stack.locator('[data-action="digest"]').click();expect(account_stack.locator('.kau-status')).to_contain_text('No email preference was changed')
    checks.append('Unchecked digest consent is rejected; unconfigured follow adapter never claims opt-in')
    active=second.new_page();active_root=workflow(active);expect(active_root.locator('#kau-name')).to_have_value('Native account commercial copy')
    stack.locator('[data-action="delete"]').click();expect(stack.locator('.kau-status')).to_contain_text('saved projects removed')
    expect(active_root.locator('#kau-name')).to_have_value('Untitled commercial')
    assert request(page,'projects')['body']==[] and request(page,'stack')['body']['projects']==[]
    assert len(request(other,'projects')['body'])==1
    assert cross.evaluate('localStorage.getItem("kingy.commercial.v1")') is None
    assert not errors,errors
    checks.append('Account deletion removes stack and standalone projects, clears device/other-tab copies, preserves other user')
    browser.close()
finally:
    COOKIE_PATH.unlink(missing_ok=True)
result={'scope':'Real disposable local WordPress native cookies, REST nonces and account persistence. No mocked authentication, production changes or real sends.','passed':len(checks),'checks':checks}
(OUT/'accounts-browser-results.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps(result))
