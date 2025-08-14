# PayPerCrawl - Invite-Only Dashboard System

## Overview

The PayPerCrawl dashboard now features an invite-only access system to control beta user access. Users must be explicitly invited by administrators to access the dashboard functionality.

## Flow Description

### 1. User Journey

#### Step 1: User tries to access dashboard

- User visits `/dashboard` directly
- **Middleware checks for invite token**
- If no token found → **Redirects to `/waitlist`**

#### Step 2: User fills waitlist form

- User submits application via `/waitlist`
- Application stored in database with `status: 'pending'`
- User receives confirmation email

#### Step 3: Admin approval process

- Admin reviews applications in `/admin`
- Admin sends invitation to approved users
- System generates unique invite token
- **Invitation email sent with dashboard access link**

#### Step 4: User accesses dashboard

- User clicks "Access Beta Dashboard" in email
- Link format: `/dashboard?token={inviteToken}`
- System validates token and grants access
- Token stored in cookie for future visits

### 2. Technical Implementation

#### Authentication Flow

```
/dashboard → Middleware → Check token → Validate → Dashboard
     ↓                                      ↓
  No token                               Invalid token
     ↓                                      ↓
/waitlist                               /waitlist
```

#### Key Components

**Middleware (`middleware.ts`)**

- Protects `/dashboard` route
- Checks for token in URL params or cookies
- Redirects to waitlist if no token found
- Sets cookie for token persistence

**Dashboard Page (`/dashboard/page.tsx`)**

- Validates invite token on load
- Shows loading state during validation
- Displays user-specific dashboard content
- Includes logout functionality

**Admin Dashboard (`/admin/page.tsx`)**

- Manage waitlist entries
- Send invitations to users
- Copy invite links
- Revoke access if needed

**Email System (`/lib/email.ts`)**

- Sends beta invitation emails
- Includes direct dashboard access links
- Professional email templates

#### API Endpoints

**Invitation Management**

- `POST /api/waitlist/invite` - Send invitation to user
- `POST /api/waitlist/validate` - Validate invite token
- `GET /api/dashboard/status` - Check user access status
- `POST /api/admin/revoke-access` - Revoke user access

**Waitlist Management**

- `POST /api/waitlist/join` - Join waitlist
- `GET /api/waitlist/check` - Check if email is on waitlist
- `GET /api/admin/waitlist` - Get all waitlist entries

### 3. Database Schema

**WaitlistEntry Model**

```typescript
{
  id: String (Primary Key)
  email: String (Unique)
  name: String
  website: String?
  status: String // 'pending', 'invited', 'accepted'
  inviteToken: String? (Unique)
  invitedAt: DateTime?
  createdAt: DateTime
  updatedAt: DateTime
}
```

**Status Flow**

- `pending` → User submitted application
- `invited` → Admin sent invitation
- `accepted` → User accessed dashboard

### 4. Security Features

**Token-based Access**

- Unique tokens for each invitation
- Tokens stored securely in httpOnly cookies
- Automatic token validation on each dashboard visit

**Admin Protection**

- Admin routes protected with API key
- Secure admin authentication system

**Middleware Protection**

- Route-level protection for sensitive areas
- Automatic redirects for unauthorized access

### 5. User Experience Features

**Seamless Access**

- One-click access from email
- Persistent login via cookies
- Loading states and feedback
- Clear error messages

**Admin Tools**

- Bulk invitation sending
- Copy invite links directly
- Revoke access capabilities
- Real-time status updates

## Usage Examples

### For Users

1. **Joining Waitlist**

   ```
   Visit: /waitlist
   Fill form → Submit → Wait for approval
   ```

2. **Accessing Dashboard**
   ```
   Receive email → Click "Access Beta Dashboard" → Automatic redirect to dashboard
   ```

### For Administrators

1. **Sending Invitations**

   ```
   Visit: /admin
   Authenticate → Waitlist tab → Send Invite button
   ```

2. **Managing Access**
   ```
   Copy invite links for manual sharing
   Revoke access for users if needed
   Monitor user status and activity
   ```

## Configuration

### Environment Variables

```env
ADMIN_API_KEY=your_admin_key
NEXT_PUBLIC_APP_URL=https://paypercrawl.tech
RESEND_API_KEY=your_resend_key
```

### Middleware Configuration

```typescript
export const config = {
  matcher: ["/dashboard"],
};
```

## Testing the System

1. **Test Unauthorized Access**
   - Visit `/dashboard` without token
   - Should redirect to `/waitlist`

2. **Test Invitation Flow**
   - Submit waitlist application
   - Use admin to send invitation
   - Check email for invitation link
   - Click link to access dashboard

3. **Test Token Persistence**
   - Access dashboard with token
   - Refresh page
   - Should remain authenticated

4. **Test Logout**
   - Click logout button
   - Should clear cookies and redirect home
   - Visiting `/dashboard` should redirect to waitlist

## Troubleshooting

**Common Issues:**

- Token not found: Check email spam folder
- Access denied: Verify admin sent invitation
- Page not loading: Check environment variables
- Email not sent: Verify Resend API key

**Debug Steps:**

1. Check browser cookies for `invite_token`
2. Verify token in database `WaitlistEntry.inviteToken`
3. Check admin dashboard for user status
4. Review server logs for errors

---

This system provides complete control over dashboard access while maintaining a smooth user experience for legitimate beta users.
