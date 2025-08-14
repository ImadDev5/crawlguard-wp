import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { token } = body;

    if (!token) {
      return NextResponse.json(
        { valid: false, error: "Token is required" },
        { status: 400 }
      );
    }

    // Find waitlist entry with this invite token and invited status
    const waitlistEntry = await db.waitlistEntry.findUnique({
      where: {
        inviteToken: token,
      },
    });

    if (!waitlistEntry) {
      return NextResponse.json(
        { valid: false, error: "Invalid invite token" },
        { status: 401 }
      );
    }

    // Check if the user is invited or already accepted
    if (
      waitlistEntry.status !== "invited" &&
      waitlistEntry.status !== "accepted"
    ) {
      return NextResponse.json(
        {
          valid: false,
          error: "Invite has been revoked or is no longer valid",
        },
        { status: 401 }
      );
    }

    // Update status to accepted if this is the first time accessing
    if (waitlistEntry.status === "invited") {
      await db.waitlistEntry.update({
        where: { id: waitlistEntry.id },
        data: { status: "accepted" },
      });
    }

    return NextResponse.json({
      valid: true,
      user: {
        email: waitlistEntry.email,
        name: waitlistEntry.name,
        website: waitlistEntry.website,
      },
    });
  } catch (error) {
    console.error("Error validating invite token:", error);
    return NextResponse.json(
      { valid: false, error: "Internal server error" },
      { status: 500 }
    );
  }
}

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const token = searchParams.get("token");

    if (!token) {
      return NextResponse.json(
        { valid: false, error: "Token is required" },
        { status: 400 }
      );
    }

    // Find waitlist entry with this invite token
    const waitlistEntry = await db.waitlistEntry.findUnique({
      where: {
        inviteToken: token,
        status: "invited",
      },
    });

    if (!waitlistEntry) {
      return NextResponse.json(
        { valid: false, error: "Invalid or expired invite token" },
        { status: 401 }
      );
    }

    return NextResponse.json({
      valid: true,
      user: {
        email: waitlistEntry.email,
        name: waitlistEntry.name,
        website: waitlistEntry.website,
      },
    });
  } catch (error) {
    console.error("Error validating invite token:", error);
    return NextResponse.json(
      { valid: false, error: "Internal server error" },
      { status: 500 }
    );
  }
}
