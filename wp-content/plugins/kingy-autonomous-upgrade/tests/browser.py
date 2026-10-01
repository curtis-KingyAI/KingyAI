"""Local browser acceptance. Stack/catalog fixtures are explicitly intercepted UI fixtures."""
import json, pathlib, struct, zlib
from playwright.sync_api import sync_playwright, expect

BASE='http://127.0.0.1:8081'
OUT=pathlib.Path('/workspace/KingyAI/docs/autonomous-upgrade/evidence')
OUT.mkdir(parents=True,exist_ok=True)
checks=[]
def record(name): checks.append(name)
def png():
    def chunk(k,b): return struct.pack('!I',len(b))+k+b+struct.pack('!I',zlib.crc32(k+b)&0xffffffff)
    return b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('!IIBBBBB',1,1,8,2,0,0,0))+chunk(b'IDAT',zlib.compress(b'\x00\x10\x80\x20'))+chunk(b'IEND',b'')

with sync_playwright() as pw:
    browser=pw.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
    for width in (1440,390):
        context=browser.new_context(viewport={'width':width,'height':1000},accept_downloads=True)
        page=context.new_page();errors=[]
        page.on('pageerror',lambda e:errors.append(str(e)))
        page.goto(BASE+'/?pagename=commercial-staging');root=page.locator('[data-kau="workflow"]')
        expect(root.locator('#kau-name')).to_be_visible()
        root.locator('#kau-name').fill('Reusable bottle commercial');root.locator('#kau-name').blur()
        for field,value in [('product','Steel bottle'),('audience','Commuters'),('message','Refill every day')]:
            root.locator('[data-field="brief.'+field+'"]').fill(value);root.locator('[data-field="brief.'+field+'"]').blur()
        root.locator('#kau-image').set_input_files({'name':'reference.png','mimeType':'image/png','buffer':png()})
        expect(root.locator('img[alt="Your product reference"]')).to_be_visible()
        root.locator('[data-action="delete-image"]').click();expect(root.locator('img[alt="Your product reference"]')).to_have_count(0)
        root.locator('#kau-image').set_input_files({'name':'fake.png','mimeType':'image/png','buffer':b'<svg onload="alert(1)"/>'})
        expect(root.locator('.kau-status')).to_contain_text('Use a PNG')
        root.locator('[data-action="next"]').click();root.locator('[data-action="outline"]').click()
        root.locator('[data-step="2"]').click();expect(root.locator('[data-field="shots.0.purpose"]')).to_be_visible()
        root.locator('[data-field="shots.0.camera"]').fill('Slow push-in, 50mm, eye level');root.locator('[data-field="shots.0.camera"]').blur()
        root.locator('[data-step="3"]').click();root.locator('[data-action="prompts"]').click()
        expect(root.locator('[data-field="prompts.0.text"]')).to_contain_text('50mm')
        root.locator('[data-step="4"]').click()
        root.locator('[data-field="budget.price"]').fill('0.2');root.locator('[data-field="budget.price"]').blur()
        expect(root.locator('#kau-budget-result')).to_contain_text('12.00 USD')
        root.locator('[data-step="5"]').click()
        with page.expect_download() as d: root.locator('[data-action="pack"]').click()
        path=OUT/f'production-pack-{width}.md';d.value.save_as(path);assert 'Steel bottle' in path.read_text()
        root.locator('#kau-save-device').check()
        with page.expect_download() as d: root.locator('[data-action="export"]').first.click()
        backup=OUT/f'commercial-{width}.json';d.value.save_as(backup)
        page.reload();expect(root.locator('[data-field="brief.product"]')).to_have_value('Steel bottle')
        root.locator('[data-field="brief.audience"]').fill('Cyclists');root.locator('[data-field="brief.audience"]').blur()
        expect(root.locator('#kau-stale')).to_be_visible();root.locator('[data-step="5"]').click()
        expect(root.locator('[data-action="pack"]')).to_be_disabled()
        root.locator('[data-step="1"]').click();root.locator('[data-action="outline"]').click()
        root.locator('[data-step="3"]').click();root.locator('[data-action="prompts"]').click()
        root.locator('[data-step="5"]').click();expect(root.locator('[data-action="pack"]')).to_be_enabled()
        root.locator('[data-action="duplicate"]').click();expect(root.locator('#kau-name')).to_have_value('Reusable bottle commercial copy')
        root.locator('#kau-import').set_input_files(str(backup))
        expect(root.locator('#kau-name')).to_have_value('Reusable bottle commercial')
        root.locator('[data-action="save-stack"]').click();expect(root.locator('.kau-status')).to_contain_text('saved in My AI Stack')
        # Keyboard focus and horizontal overflow on the extension, independent of the default WP theme.
        root.locator('#kau-name').focus();page.keyboard.press('Tab');assert page.evaluate('document.activeElement.tagName')=='BUTTON'
        assert root.evaluate('(el)=>el.scrollWidth<=el.clientWidth+2')
        page.screenshot(path=str(OUT/f'workflow-{width}.png'),full_page=True)
        record(f'{width}px workflow: complete, upload validation/deletion, backtrack, refresh, stale revision, duplicate, export/import, save stack, keyboard, no extension overflow')
        # UI test fixtures are not real products and do not bypass server publication gates.
        products=[{'id':'wp:kingy_ai_tool:99901','name':'STAGING UI FIXTURE — Video tool','url':'https://kingy.ai/ai-tools/','price':None,'coverage':{'status':'unconfirmed','verified_at':None}}]
        changes=[{'product_id':'wp:kingy_ai_tool:99901','title':'STAGING UI FIXTURE price change','summary':'Fixture price changed.','why_it_matters':'Your planned budget may need revision.','source_url':'https://example.org/fixture','verified_at':'2026-10-01T12:00:00Z','guide_url':'https://kingy.ai/make-this/'}]
        def fixture(route):
            route.fulfill(status=200,content_type='application/json',body=json.dumps(products if 'catalog' in route.request.url else changes))
        page.route('**/kingy-upgrade/v1/catalog*',fixture);page.route('**/kingy-upgrade/v1/changes*',fixture)
        page.goto(BASE+'/?pagename=my-ai-stack-staging');stack=page.locator('[data-kau="stack"]')
        stack.get_by_role('button',name='Save STAGING UI FIXTURE').click()
        expect(stack).to_contain_text('Your planned budget may need revision.')
        stack.locator('[data-category="0"]').fill('Video');stack.locator('[data-category="0"]').blur()
        page.reload();expect(stack.locator('[data-category="0"]')).to_have_value('Video')
        with page.expect_download() as d: stack.locator('[data-action="export"]').click()
        stackfile=OUT/f'stack-{width}.json';d.value.save_as(stackfile)
        stack.locator('[data-action="delete"]').click();expect(stack).to_contain_text('Your stack is empty')
        stack.locator('#kau-stack-import').set_input_files(str(stackfile));expect(stack.locator('[data-category="0"]')).to_have_value('Video')
        stack.locator('[data-remove="0"]').click();expect(stack).to_contain_text('No published verified changes currently match')
        assert not errors,errors
        record(f'{width}px stack UI fixtures: save/remove/category, relevance, project persistence, export/import/deletion; no browser errors')
        context.close()
    browser.close()
result={'scope':'Local WordPress browser journeys. Catalog/change responses for stack positive UI tests are simulated. No production mutation or remote inference.','passed':len(checks),'checks':checks}
(OUT/'browser-results.json').write_text(json.dumps(result,indent=2));print(json.dumps(result))
