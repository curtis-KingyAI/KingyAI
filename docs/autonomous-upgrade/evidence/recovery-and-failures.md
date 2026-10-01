# Failure and recovery evidence

Scope: this run's independent local work and public reads. No production mutation or live send experiment.

| Observation | Diagnosis | Targeted response and outcome |
|---|---|---|
| Official YouTube channel, feed and oEmbed returned proxy tunnel 403 | Provider/environment route restriction, not evidence of an empty channel | Held channel/newest-five identities; no bypass via transcript scraping or unsupported alternatives; pipeline tested with explicit synthetic Atom feed |
| Docker Hub anonymous pull quota denied staging image | Registry quota, not plugin defect | Used official public ECR library mirrors; disposable PHP 8.3/MariaDB 11.4 staging available |
| Default Python public fetch User-Agent was denied on some Kingy requests | Request policy; subsequent permitted identified client returned public pages | Used identified `KingyUpgradeDiscovery/1.0` requests and retained individual status/date/fingerprint evidence; did not label failed checking as zero results |
| First test report had empty check accumulator despite execution | WP CLI eval-file scope versus test accumulator scope | Moved accumulator to explicit test globals; reran harness and retained named checks |
| Repeated synthetic email harness run reused a previous policy key | Disposable fixture identity collided with durable accepted outbox | Scoped fixture policy to newly created fixture product; kept duplicate invocation assertion within each run; no accepted record reset |
| Incomplete synthetic launch did not pass existing public quality/index gates | Real meaningful publication requirements remained unmet | Asserted that incomplete fixture is held; browser positive stack records explicitly use intercepted UI fixtures rather than lowering release gates |
| Existing companion could be left drafted after a failed update | Working record was staged before the existing publication gate rejected the update | Added raw-record/postmeta recovery on every later failure; tested both publication gate rejection and throwing metadata hook; original URL/content/status restored |
| Test-mode source batch could count schema-valid but unsupported facts | Simulation used weaker gate than live admission | Shared pure canonical verification gate in test/live; unsupported batch records actionable exception distinct from real zero |
| A daily digest could include an already accepted event under another day key | Recipient/policy dedup alone did not exclude earlier-day events | Added recipient/event history check including sending/uncertain/accepted/delivered; tested uncertain and accepted exclusion across daily policies |
| Default recipient processing could partially handle a list above its bound | Unconfigured bulk integration | Hold before archive/outbox/send work for lists over 500; tested no partial outbox mutation |
| Expiry or later consent revocation could erase uncertainty about an earlier send | Eligibility was evaluated before provider reconciliation | Reconcile prior sending/uncertain state first; preserve ambiguity after expiration/opt-out; require explicit definitely-not-sent before permitting a later retry. Tested expiry, revoked consent, malformed queued reconciliation and accepted reconciliation without a resend |
| WordPress emits early KALI translation-loading notice | Inherited plugin initialization on newer core | Retained notice and log; no suppression. Production source/core compatibility still requires restored staging |

Final named check results are in the neighboring JSON/text artifacts. Local failures were corrected without weakening canonical evidence, disclosure, consent, publication or duplicate-send gates. Fetches did not refresh unsupported facts. Meaningful live delivery and provider failure experiments remain unperformed because no authenticated established provider account was available.
