import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "نواة الإعلانات | Google Ads Control Center",
  description: "لوحة تحكم آمنة لإدارة حسابات Google Ads وفرق الوكالات",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ar" dir="rtl">
      <body className="min-h-screen">{children}</body>
    </html>
  );
}
