import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Send } from 'lucide-react';

interface ChatbotPanelProps {
    isOpen: boolean;
    onClose: () => void;
}

export function ChatbotPanel({ isOpen, onClose }: ChatbotPanelProps) {
    return (
        <Sheet open={isOpen} onOpenChange={onClose}>
            <SheetContent className="flex flex-col">
                <SheetHeader>
                    <SheetTitle>Scheduling Assistant</SheetTitle>
                </SheetHeader>
                <div className="flex-grow overflow-y-auto p-4">
                    <div className="space-y-4">
                        {/* Mock chat messages */}
                        <div className="flex items-end gap-2">
                            <div className="rounded-lg bg-blue-500 p-3 text-white">
                                <p>Hello! How can I help you schedule today?</p>
                            </div>
                        </div>
                        <div className="flex items-end justify-end gap-2">
                            <div className="rounded-lg bg-gray-200 p-3 dark:bg-gray-700">
                                <p>I need to book an appointment with Dr. Connor.</p>
                            </div>
                        </div>
                        <div className="flex items-end gap-2">
                            <div className="rounded-lg bg-blue-500 p-3 text-white">
                                <p>Of course. Dr. Connor's next availability is November 28th at 10:00 AM. Does that work for you?</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="flex items-center gap-2 border-t p-4">
                    <Input placeholder="Type your message..." />
                    <Button variant="ghost" size="icon">
                        <Send className="h-5 w-5" />
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
