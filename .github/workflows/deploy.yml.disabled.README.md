# Auto-deploy disabled 2026-04-17

Reason: `AmaraEnterprises` was deploying to the same Firebase project
(`amara-business-system`) as the private `Amara-Business-Systems` repo, causing
them to overwrite each other at `amara-business-system.web.app`.

Going forward, marketing + dashboard will be consolidated into the private repo
`jolly-b09/Amara-Business-Systems`. This workflow is retained (disabled) for
history. To re-enable, rename `deploy.yml.disabled` -> `deploy.yml`.
