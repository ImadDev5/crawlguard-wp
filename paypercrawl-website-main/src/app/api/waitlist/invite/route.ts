import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { sendBetaInvite } from "@/lib/email";
import { z } from "zod";
import { randomBytes } from "crypto";

const inviteSchema = z.object({
  email: z.string().email("Invalid email address"),
  adminKey: z.string().min(1, "Admin key required"),
});

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { email, adminKey } = inviteSchema.parse(body);

    // Verify admin key
    if (adminKey !== process.env.ADMIN_API_KEY) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Find waitlist entry
    const waitlistEntry = await db.waitlistEntry.findUnique({
      where: { email },
    });

    if (!waitlistEntry) {
      return NextResponse.json(
        { error: "Email not found on waitlist" },
        { status: 404 }
      );
    }

    if (waitlistEntry.status === "invited") {
      return NextResponse.json(
        { error: "User already invited" },
        { status: 400 }
      );
    }

    // Generate invite token if not exists
    let inviteToken = waitlistEntry.inviteToken;
    if (!inviteToken) {
      inviteToken = randomBytes(32).toString("hex");
    }

    // Update status to invited
    const updatedEntry = await db.waitlistEntry.update({
      where: { email },
      data: {
        status: "invited",
        inviteToken,
        invitedAt: new Date(),
      },
    });

    // Send beta invite email
    try {
      console.log(`Attempting to send beta invite email to ${email}`);
      await sendBetaInvite(email, waitlistEntry.name, inviteToken);
      console.log(`Successfully sent beta invite email to ${email}`);
    } catch (emailError) {
      console.error("Failed to send beta invite email:", emailError);
      console.error(
        "Email error details:",
        JSON.stringify(emailError, null, 2)
      );
      // Even if email fails, we still consider the invite sent
      // The admin can manually send the invite later
    }
    return NextResponse.json({
      message: "Beta invite sent successfully",
      entry: updatedEntry,
    });
  } catch (error) {
    console.error("Invite error:", error);

    if (error instanceof z.ZodError) {
      return NextResponse.json(
        { error: "Validation failed", details: error.issues },
        { status: 400 }
      );
    }

    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const adminKey = searchParams.get("adminKey");

    if (adminKey !== process.env.ADMIN_API_KEY) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const invitedUsers = await db.waitlistEntry.findMany({
      where: { status: "invited" },
      orderBy: { invitedAt: "desc" },
    });

    return NextResponse.json(invitedUsers);
  } catch (error) {
    console.error("Error fetching invited users:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
