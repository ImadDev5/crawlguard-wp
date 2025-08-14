import { Suspense } from "react";
import DashboardClient from "./DashboardClient";

// Disable static generation for this page
export const dynamic = "force-dynamic";

export default function DashboardPage() {
  return (
    <Suspense fallback={null}>
      <DashboardClient />
    </Suspense>
  );
}
