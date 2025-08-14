import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const email = searchParams.get("email");
    const token = searchParams.get("token");

    if (!email && !token) {
      return NextResponse.json(
        { error: "Email or token is required" },
        { status: 400 }
      );
    }

    let waitlistEntry;

    if (token) {
      // Check by token
      waitlistEntry = await db.waitlistEntry.findUnique({
        where: { inviteToken: token },
      });
    } else if (email) {
      // Check by email
      waitlistEntry = await db.waitlistEntry.findUnique({
        where: { email },
      });
    }

    if (!waitlistEntry) {
      return NextResponse.json({
        hasAccess: false,
        status: "not_found",
        message: "User not found in waitlist",
      });
    }

    const hasAccess =
      waitlistEntry.status === "invited" || waitlistEntry.status === "accepted";

    return NextResponse.json({
      hasAccess,
      status: waitlistEntry.status,
      user: hasAccess
        ? {
            email: waitlistEntry.email,
            name: waitlistEntry.name,
            website: waitlistEntry.website,
            invitedAt: waitlistEntry.invitedAt,
          }
        : null,
      message: hasAccess
        ? "User has dashboard access"
        : `User status: ${waitlistEntry.status}`,
    });
  } catch (error) {
    console.error("Error checking access status:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
