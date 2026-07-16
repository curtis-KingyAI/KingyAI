# Kingy AI Agent Directory

Server-rendered replacement for the inline Agent Directory application on WordPress page `15708`.

## Data contract

- Tool post type: `kingy_ai_tool`
- Required taxonomy: `kingy_launch_category`, term ID `2395`
- Product facts come only from the matching `kingy_ai_tool` record:
  - `_kingy_ali_official_url`
  - `_kingy_ali_what_it_does`
  - `_kingy_ali_last_verified`
- The versioned approval manifest in `data/approved-agents.json` contains only the editorial allowlist, type, limited-availability disclosure, and audit date.
- A record must be published, assigned to term `2395`, present in the allowlist, title-matched, and complete before it can render.

The plugin does not mutate tool records during deployment. Editorial additions or removals are reviewed in the manifest and become visible after the page/object cache is purged.

## Page content

Page 15708 should contain only:

```text
[kingy_agent_directory]
```

The theme supplies the only H1 and renders the WordPress featured image. The shortcode supplies the readiness scorecard, source-checked directory, and commercial panels.

## Rollback

1. Set page 15708 to `noindex, follow` before restoring the old crawler-empty page.
2. Restore the pre-deployment page revision and metadata backup.
3. Deactivate this plugin.
4. Restore tool metadata from the pre-deployment database snapshot if necessary.
5. Purge object/page/CDN caches and verify the resulting page remains noindex.
