import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";

// Verify admin authentication
function verifyAdminAuth(request: NextRequest): boolean {
  const authHeader = request.headers.get("authorization");
  const adminKey = authHeader?.replace("Bearer ", "");
  return adminKey === process.env.ADMIN_API_KEY;
}

export async function POST(request: NextRequest) {
  try {
    if (!verifyAdminAuth(request)) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const { email } = await request.json();

    if (!email) {
      return NextResponse.json({ error: "Email is required" }, { status: 400 });
    }

    // Find and revoke access for the user
    const waitlistEntry = await db.waitlistEntry.findUnique({
      where: { email },
    });

    if (!waitlistEntry) {
      return NextResponse.json({ error: "User not found" }, { status: 404 });
    }

    // Update status to revoked and clear invite token
    const updatedEntry = await db.waitlistEntry.update({
      where: { email },
      data: {
        status: "pending", // Reset to pending
        inviteToken: null,
        invitedAt: null,
      },
    });

    return NextResponse.json({
      message: "Access revoked successfully",
      entry: updatedEntry,
    });
  } catch (error) {
    console.error("Error revoking access:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
