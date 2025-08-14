import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const email = searchParams.get("email");

    if (!email) {
      return NextResponse.json({ error: "Email is required" }, { status: 400 });
    }

    // Check if user exists in waitlist
    const waitlistEntry = await db.waitlistEntry.findUnique({
      where: { email },
    });

    if (!waitlistEntry) {
      return NextResponse.json({
        exists: false,
        status: null,
      });
    }

    return NextResponse.json({
      exists: true,
      status: waitlistEntry.status,
      name: waitlistEntry.name,
      createdAt: waitlistEntry.createdAt,
      invitedAt: waitlistEntry.invitedAt,
    });
  } catch (error) {
    console.error("Error checking waitlist status:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
