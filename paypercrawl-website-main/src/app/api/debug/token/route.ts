import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";

export async function GET(request: NextRequest) {
  try {
    const url = new URL(request.url);
    const token = url.searchParams.get("token");
    const cookieToken = request.cookies.get("invite_token")?.value;

    console.log("Debug Token Check:");
    console.log("URL Token:", token);
    console.log("Cookie Token:", cookieToken);

    if (!token && !cookieToken) {
      return NextResponse.json({
        error: "No token provided",
        urlToken: token,
        cookieToken: cookieToken,
      });
    }

    const tokenToCheck = token || cookieToken;

    // Find waitlist entry
    const waitlistEntry = await db.waitlistEntry.findUnique({
      where: {
        inviteToken: tokenToCheck,
      },
    });

    return NextResponse.json({
      tokenProvided: tokenToCheck,
      tokenExists: !!waitlistEntry,
      tokenStatus: waitlistEntry?.status || "not_found",
      userEmail: waitlistEntry?.email || "not_found",
      userName: waitlistEntry?.name || "not_found",
      urlToken: token,
      cookieToken: cookieToken,
    });
  } catch (error) {
    console.error("Debug token error:", error);
    return NextResponse.json(
      {
        error: "Internal server error",
        details: error instanceof Error ? error.message : "Unknown error",
      },
      { status: 500 }
    );
  }
}
