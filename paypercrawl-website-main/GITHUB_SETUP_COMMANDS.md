# GitHub Repository Setup Commands

## After creating the repository on GitHub, run these commands:

```bash
# Add the remote repository (replace YOUR_USERNAME with your actual GitHub username)
git remote add origin https://github.com/YOUR_USERNAME/paypercrawl-website.git

# Rename the default branch to main (if needed)
git branch -M main

# Push the code to GitHub
git push -u origin main
```

## Alternative SSH method (if you have SSH keys set up):

```bash
# Add the remote repository using SSH
git remote add origin git@github.com:YOUR_USERNAME/paypercrawl-website.git

# Rename the default branch to main (if needed)
git branch -M main

# Push the code to GitHub
git push -u origin main
```

## Verification:

After pushing, you should see all your files on GitHub at:
`https://github.com/YOUR_USERNAME/paypercrawl-website`

## Next Steps After GitHub Setup:

1. **Deploy to Vercel**:
   - Go to [vercel.com](https://vercel.com)
   - Click "New Project"
   - Import from GitHub
   - Select your `paypercrawl-website` repository
   - Add environment variables (see README.md for the list)
   - Deploy!

2. **Environment Variables for Vercel**:
   ```
   DATABASE_URL=postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require
   RESEND_API_KEY=re_XoKutthW_7c2446bUYzVSuf9hYLqvJmpd
   NEXT_PUBLIC_APP_URL=https://paypercrawl.tech
   ADMIN_API_KEY=paypercrawl_admin_2025_secure_key
   NODE_ENV=production
   ```

3. **Update DNS**:
   - In Cloudflare, point your A records to Vercel's IP
   - Or use Vercel's custom domain feature

## Your repository is ready! 🎉

All your PayPerCrawl website code is committed and ready to push to GitHub.
