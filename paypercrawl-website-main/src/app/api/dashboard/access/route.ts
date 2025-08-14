import { NextRequest, NextResponse } from "next/server";

export async function GET(request: NextRequest) {
  const { searchParams } = new URL(request.url);
  const token = searchParams.get("token");

  if (!token) {
    return NextResponse.redirect(new URL("/waitlist", request.url));
  }

  // Redirect to dashboard with token
  return NextResponse.redirect(
    new URL(`/dashboard?token=${token}`, request.url)
  );
}
