"use client";

import { use } from "react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Shield,
  Calendar,
  User,
  ArrowLeft,
  Clock,
  Share2,
  BookOpen,
  ChevronRight,
} from "lucide-react";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ModeToggle } from "@/components/mode-toggle";

// Blog post data with full content
const blogPosts = {
  "ai-revolutionizing-content-revenue": {
    id: "ai-revolutionizing-content-revenue",
    title: "How AI is Revolutionizing Content Revenue Models",
    excerpt:
      "The shift from ad-based revenue to AI licensing: How ChatGPT and Perplexity are changing the way publishers monetize content.",
    author: "Md Imad",
    date: "July 31, 2025",
    readTime: "8 min read",
    tags: ["AI", "Revenue Models", "Content Licensing"],
    featured: true,
    content: `
      <h2>The End of Traditional Advertising</h2>
      <p>For decades, content creators and publishers have relied on display advertising as their primary revenue source. But the landscape is changing rapidly. As AI assistants like ChatGPT, Claude, and Perplexity become the primary interface for information consumption, traditional page views are declining.</p>
      
      <p>The numbers tell a stark story: while AI model usage has grown by 300% in 2024, traditional web traffic to content sites has decreased by 15-20%. This shift represents a fundamental change in how content is consumed and monetized.</p>
      
      <h2>The Rise of AI Content Licensing</h2>
      <p>AI companies need high-quality, up-to-date content to train their models and provide accurate responses. This creates a new revenue opportunity for content creators: licensing their content directly to AI companies.</p>
      
      <p>Companies like OpenAI, Anthropic, and Google are already paying millions to publishers for content licensing deals. The New York Times signed a deal worth over $100 million, and Reddit's content licensing agreements are generating $200+ million annually.</p>
      
      <h2>How PayPerCrawl Enables This Transition</h2>
      <p>PayPerCrawl makes AI content licensing accessible to every WordPress publisher, not just major media companies. Our platform:</p>
      
      <ul>
        <li><strong>Detects AI bots</strong> crawling your content in real-time</li>
        <li><strong>Monetizes each crawl</strong> through micro-payments</li>
        <li><strong>Provides analytics</strong> on which content is most valuable to AI companies</li>
        <li><strong>Handles negotiations</strong> and payment processing automatically</li>
      </ul>
      
      <h2>The Future of Content Monetization</h2>
      <p>We're moving toward a world where content creators are compensated fairly for their work, regardless of how it's consumed. Whether a human reads your article or an AI model uses it to answer questions, you should be paid.</p>
      
      <p>This shift will create more sustainable revenue streams for publishers and incentivize the creation of high-quality, factual content that benefits both human readers and AI systems.</p>
      
      <h2>Getting Started</h2>
      <p>The transition to AI content licensing doesn't happen overnight, but early adopters will have significant advantages. Publishers who start monetizing AI traffic now will be better positioned as this market matures.</p>
      
      <p>PayPerCrawl's beta program offers 100% revenue share, making it risk-free to start experimenting with AI monetization alongside your existing revenue streams.</p>
    `,
  },
  "blog-readers-to-ai-models": {
    id: "blog-readers-to-ai-models",
    title: "From Blog Readers to AI Models: The New Attention Economy",
    excerpt:
      "Understanding how AI assistants are becoming the primary consumers of content and what it means for publisher revenue streams.",
    author: "Md Imad",
    date: "July 31, 2025",
    readTime: "6 min read",
    tags: ["AI", "Attention Economy", "Publishing"],
    featured: false,
    content: `
      <h2>The Shift in Content Consumption</h2>
      <p>The attention economy is undergoing its most significant transformation since the advent of social media. Instead of humans directly consuming content, AI models are increasingly becoming the primary "readers" of online content.</p>
      
      <p>This shift has profound implications for how publishers think about their audience, content strategy, and revenue models.</p>
      
      <h2>AI Models as Content Consumers</h2>
      <p>AI assistants like ChatGPT, Claude, and Perplexity don't just read content—they process, understand, and synthesize information from thousands of sources to provide answers to users. This makes them incredibly valuable consumers of content, but traditional advertising models don't capture this value.</p>
      
      <h2>The Value of AI Attention</h2>
      <p>When an AI model uses your content to answer a question, it's providing value to the end user. That value should flow back to the content creator. PayPerCrawl enables this by creating a direct payment mechanism for AI content consumption.</p>
      
      <h2>Adapting Your Content Strategy</h2>
      <p>Publishers need to think about creating content that serves both human readers and AI models. This means:</p>
      
      <ul>
        <li>Clear, factual information that AI models can easily parse</li>
        <li>Comprehensive coverage of topics</li>
        <li>Regular updates to maintain relevance</li>
        <li>Structured data that helps AI models understand context</li>
      </ul>
      
      <p>The future belongs to publishers who can successfully serve both audiences.</p>
    `,
  },
  "cloudflare-http-402-payment-layer": {
    id: "cloudflare-http-402-payment-layer",
    title: "Cloudflare's HTTP 402: Building the Payment Layer for AI Content",
    excerpt:
      "How Cloudflare's Pay-Per-Crawl initiative is creating the infrastructure for the future of content monetization.",
    author: "Md Imad",
    date: "July 31, 2025",
    readTime: "7 min read",
    tags: ["Cloudflare", "HTTP 402", "AI Payments"],
    featured: false,
    content: `
      <h2>The HTTP 402 Status Code</h2>
      <p>HTTP 402 "Payment Required" has been reserved since the early days of the web but never widely implemented. Cloudflare is changing that with their Pay-Per-Crawl initiative, creating the infrastructure for micropayments on the web.</p>

      <h2>Why Now?</h2>
      <p>The rise of AI has created a new class of content consumers that can afford to pay for access. Unlike human users who might be deterred by paywalls, AI companies have the resources and business need to pay for content access.</p>

      <h2>How PayPerCrawl Leverages This</h2>
      <p>Our platform builds on Cloudflare's infrastructure to:</p>
      <ul>
        <li>Detect AI bots in real-time</li>
        <li>Implement HTTP 402 responses for monetization</li>
        <li>Handle payment processing automatically</li>
        <li>Provide detailed analytics and reporting</li>
      </ul>

      <h2>The Technical Implementation</h2>
      <p>When an AI bot is detected, PayPerCrawl returns an HTTP 402 status with payment instructions. The bot can then choose to pay for access or move on to other sources. This creates a fair marketplace for content.</p>

      <p>The beauty of this system is that it's completely transparent to human users—they continue to access content normally while AI bots contribute to your revenue stream.</p>
    `,
  },
  "decline-ad-revenue-ai-licensing": {
    id: "decline-ad-revenue-ai-licensing",
    title: "The Decline of Ad Revenue: Why AI Licensing is the Future",
    excerpt:
      "As users shift from traditional browsing to AI queries, publishers need new monetization strategies beyond display advertising.",
    author: "Md Imad",
    date: "July 31, 2025",
    readTime: "9 min read",
    tags: ["Ad Revenue", "AI Licensing", "Publishing Strategy"],
    featured: false,
    content: `
      <h2>The Numbers Don't Lie</h2>
      <p>Display advertising revenue has been declining steadily as users shift to AI assistants for information. Google searches are down 15% year-over-year, while ChatGPT usage has grown 300%.</p>

      <h2>Why Traditional Ads Are Failing</h2>
      <p>AI assistants don't click on ads. They consume content, process it, and provide answers without generating ad revenue for publishers. This fundamental shift requires new monetization strategies.</p>

      <h2>The AI Licensing Opportunity</h2>
      <p>Instead of hoping for ad clicks, publishers can license their content directly to AI companies. This creates a more stable, predictable revenue stream that scales with content quality rather than traffic volume.</p>

      <h2>Making the Transition</h2>
      <p>Publishers don't need to abandon advertising entirely. The smart strategy is to diversify revenue streams by adding AI licensing alongside existing monetization methods.</p>

      <p>PayPerCrawl makes this transition seamless by automatically detecting and monetizing AI traffic while preserving your existing ad setup for human visitors.</p>
    `,
  },
};

interface BlogPostPageProps {
  params: Promise<{
    slug: string;
  }>;
}

export default function BlogPostPage({ params }: BlogPostPageProps) {
  const { slug } = use(params);
  const post = blogPosts[slug as keyof typeof blogPosts];

  if (!post) {
    notFound();
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Navigation */}
      <nav className="sticky top-0 z-50 bg-background/80 backdrop-blur-md border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <Link href="/" className="flex items-center space-x-2">
              <Shield className="h-8 w-8 text-primary" />
              <span className="text-xl font-bold text-foreground">
                PayPerCrawl
              </span>
            </Link>
            <div className="hidden md:flex items-center space-x-8">
              <Link
                href="/"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                Home
              </Link>
              <Link
                href="/features"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                Features
              </Link>
              <Link
                href="/about"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                About
              </Link>
              <Link href="/blog" className="text-primary font-medium">
                Blog
              </Link>
              <Link href="/waitlist">
                <Button>Join Beta</Button>
              </Link>
              <ModeToggle />
            </div>
          </div>
        </div>
      </nav>

      {/* Breadcrumb */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center gap-2 text-sm text-muted-foreground mb-8">
          <Link href="/" className="hover:text-foreground">
            Home
          </Link>
          <ChevronRight className="h-4 w-4" />
          <Link href="/blog" className="hover:text-foreground">
            Blog
          </Link>
          <ChevronRight className="h-4 w-4" />
          <span className="text-foreground">{post.title}</span>
        </div>
      </div>

      {/* Article Header */}
      <article className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <header className="mb-12">
          <div className="flex items-center gap-2 mb-4">
            {post.tags.map((tag, index) => (
              <Badge key={index} variant="secondary" className="text-sm">
                {tag}
              </Badge>
            ))}
          </div>

          <h1 className="text-4xl lg:text-5xl font-bold text-foreground mb-6 leading-tight">
            {post.title}
          </h1>

          <p className="text-xl text-muted-foreground mb-8 leading-relaxed">
            {post.excerpt}
          </p>

          <div className="flex items-center justify-between border-t border-b border-border py-6">
            <div className="flex items-center gap-6 text-sm text-muted-foreground">
              <div className="flex items-center gap-2">
                <User className="h-4 w-4" />
                <span>{post.author}</span>
              </div>
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4" />
                <span>{post.date}</span>
              </div>
              <div className="flex items-center gap-2">
                <Clock className="h-4 w-4" />
                <span>{post.readTime}</span>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm">
                <Share2 className="h-4 w-4 mr-2" />
                Share
              </Button>
            </div>
          </div>
        </header>

        {/* Article Content */}
        <div
          className="prose prose-lg max-w-none mb-16
                       prose-headings:text-foreground prose-headings:font-bold
                       prose-h2:text-2xl prose-h2:mt-12 prose-h2:mb-6
                       prose-p:text-muted-foreground prose-p:leading-relaxed prose-p:mb-6
                       prose-ul:my-6 prose-li:text-muted-foreground prose-li:mb-2
                       prose-strong:text-foreground prose-strong:font-semibold
                       dark:prose-invert"
        >
          <div dangerouslySetInnerHTML={{ __html: post.content }} />
        </div>

        {/* Article Footer */}
        <footer className="border-t border-border pt-12 mb-16">
          <div className="flex items-center justify-between">
            <Link href="/blog">
              <Button variant="outline">
                <ArrowLeft className="h-4 w-4 mr-2" />
                Back to Blog
              </Button>
            </Link>

            <div className="flex items-center gap-4">
              <span className="text-sm text-muted-foreground">
                Share this article:
              </span>
              <Button variant="outline" size="sm">
                <Share2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </footer>
      </article>

      {/* Related Articles */}
      <section className="bg-muted/30 py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-2xl font-bold text-foreground mb-8">
            Related Articles
          </h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {Object.values(blogPosts)
              .filter((p) => p.id !== post.id)
              .slice(0, 3)
              .map((relatedPost) => (
                <Card
                  key={relatedPost.id}
                  className="border-0 shadow-lg hover:shadow-xl transition-shadow"
                >
                  <CardHeader>
                    <div className="flex items-center gap-2 mb-3">
                      {relatedPost.tags.slice(0, 2).map((tag, tagIndex) => (
                        <Badge
                          key={tagIndex}
                          variant="secondary"
                          className="text-xs"
                        >
                          {tag}
                        </Badge>
                      ))}
                    </div>
                    <Link href={`/blog/${relatedPost.id}`}>
                      <CardTitle className="text-lg hover:text-primary transition-colors cursor-pointer">
                        {relatedPost.title}
                      </CardTitle>
                    </Link>
                    <CardDescription>{relatedPost.excerpt}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Clock className="h-4 w-4" />
                        <span>{relatedPost.readTime}</span>
                      </div>
                      <Link href={`/blog/${relatedPost.id}`}>
                        <Button variant="ghost" size="sm">
                          Read More
                        </Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>
              ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-24">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="bg-gradient-to-r from-primary to-primary/80 rounded-2xl p-12 text-primary-foreground">
            <BookOpen className="h-12 w-12 mx-auto mb-6 text-primary-foreground/80" />
            <h2 className="text-3xl font-bold mb-4">
              Ready to Monetize Your Content?
            </h2>
            <p className="text-xl text-primary-foreground/80 mb-8">
              Join our beta program and start earning from AI bot traffic today
            </p>
            <Link href="/waitlist">
              <Button size="lg" variant="secondary">
                Join Beta Program
              </Button>
            </Link>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-background border-t border-border py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="flex items-center space-x-2 mb-4 md:mb-0">
              <Shield className="h-8 w-8 text-primary" />
              <span className="text-xl font-bold text-foreground">
                PayPerCrawl
              </span>
            </div>
            <div className="text-muted-foreground text-center md:text-right">
              <p>&copy; 2025 PayPerCrawl. All rights reserved.</p>
              <p className="text-sm mt-1">The Cloudflare for WordPress</p>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
