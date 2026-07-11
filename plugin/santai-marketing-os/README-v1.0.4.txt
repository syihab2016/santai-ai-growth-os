Santai AI Growth OS v1.0.4

Critical Facebook fixes:
1. Removed conflicting Facebook token fields from General Settings.
2. One source of truth synced to Page 1.
3. Accepts User Token or Page Token.
4. Automatically converts User Token to correct Page Token using /me/accounts.
5. Test verifies /me identity equals Page ID.
6. Facebook publishing service rebuilt using Graph API v25.0.
7. Added API logs.

Why v1.0.3 failed:
- Connection test used one token.
- Campaign publishing could use an older conflicting Page 1 token.
- A User Token can read Page identity but cannot publish as the Page.
