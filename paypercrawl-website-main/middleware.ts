import { NextRequest, NextResponse } from "next/server";

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Get the invite token from cookies
  const inviteToken = request.cookies.get("invite_token")?.value;

  // Special handling for dashboard route
  if (pathname === "/dashboard") {
    // Check for token in URL params (for new invitations) or cookies
    const urlToken = request.nextUrl.searchParams.get("token");
    const tokenToUse = urlToken || inviteToken;

    // If no token at all, redirect to waitlist
    if (!tokenToUse) {
      return NextResponse.redirect(new URL("/waitlist", request.url));
    }

    // If token comes from URL, store it in cookie
    if (urlToken) {
      const response = NextResponse.next();
      response.cookies.set("invite_token", urlToken, {
        httpOnly: false, // Allow client-side access for logout
        secure: process.env.NODE_ENV === "production",
        sameSite: "lax",
        maxAge: 60 * 60 * 24 * 30, // 30 days
        path: "/", // Make cookie available across entire site
      });
      return response;
    }

    return NextResponse.next();
  }

  // For all other routes, just continue - the user stays "logged in"
  // The authentication state is preserved in the cookie
  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - api (API routes)
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    "/((?!api|_next/static|_next/image|favicon.ico).*)",
  ],
};
