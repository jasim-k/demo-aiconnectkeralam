import { Form, Head, usePage } from '@inertiajs/react';
import TelegramController from '@/actions/App/Http/Controllers/Settings/TelegramController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/telegram';

type TelegramStatus = {
    connected: boolean;
    username: string | null;
    connected_at: string | null;
    pair_code: string | null;
    pair_code_expires_at: string | null;
    pair_deep_link: string | null;
    pair_qr_svg: string | null;
};

type PageProps = {
    telegram: TelegramStatus;
    botUsername: string | null;
};

function formatDate(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString();
}

export default function Telegram() {
    const { telegram, botUsername } = usePage<PageProps>().props;

    const botHandle = botUsername?.replace(/^@/, '') ?? null;
    const botLink = botHandle ? `https://t.me/${botHandle}` : null;

    return (
        <>
            <Head title="Telegram settings" />

            <h1 className="sr-only">Telegram settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Telegram"
                    description="Connect your Telegram account to receive order confirmations automatically."
                />

                {telegram.connected ? (
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Badge>Connected</Badge>
                            {telegram.username && (
                                <span className="text-sm text-muted-foreground">
                                    @{telegram.username}
                                </span>
                            )}
                        </div>

                        {telegram.connected_at && (
                            <p className="text-sm text-muted-foreground">
                                Connected on {formatDate(telegram.connected_at)}
                                .
                            </p>
                        )}

                        <Form
                            {...TelegramController.destroy.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                    data-test="disconnect-telegram-button"
                                >
                                    Disconnect
                                </Button>
                            )}
                        </Form>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {telegram.pair_code ? (
                            <div className="space-y-4">
                                <ol className="list-decimal space-y-2 pl-5 text-sm text-muted-foreground">
                                    <li>
                                        Open the Telegram bot
                                        {botLink ? (
                                            <>
                                                {' '}
                                                <a
                                                    href={botLink}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="font-medium text-foreground underline"
                                                >
                                                    @{botHandle}
                                                </a>
                                            </>
                                        ) : (
                                            ' in Telegram'
                                        )}
                                        .
                                    </li>
                                    <li>
                                        Send this message to the bot:{' '}
                                        <code className="rounded bg-muted px-1 py-0.5 font-mono">
                                            /pair {telegram.pair_code}
                                        </code>
                                    </li>
                                </ol>

                                <div className="flex flex-col gap-4 sm:flex-row sm:items-stretch">
                                    <div className="flex-1 rounded-md border border-dashed p-4">
                                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                            Your pairing code
                                        </p>
                                        <p className="mt-1 font-mono text-2xl font-semibold">
                                            {telegram.pair_code}
                                        </p>
                                        {telegram.pair_code_expires_at && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Expires{' '}
                                                {formatDate(
                                                    telegram.pair_code_expires_at,
                                                )}
                                                .
                                            </p>
                                        )}
                                    </div>

                                    {telegram.pair_qr_svg && (
                                        <div className="flex flex-col items-center justify-center gap-2 rounded-md border border-dashed p-4">
                                            <img
                                                src={telegram.pair_qr_svg}
                                                alt="Scan to connect Telegram"
                                                className="size-36 rounded bg-white p-2"
                                                data-test="telegram-pair-qr"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                Scan to open the bot and connect
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {telegram.pair_deep_link && (
                                    <Button asChild variant="outline">
                                        <a
                                            href={telegram.pair_deep_link}
                                            target="_blank"
                                            rel="noreferrer"
                                            data-test="telegram-open-deep-link"
                                        >
                                            Open in Telegram
                                        </a>
                                    </Button>
                                )}

                                <Form
                                    {...TelegramController.store.form()}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                            data-test="regenerate-telegram-code-button"
                                        >
                                            Generate a new code
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        ) : (
                            <Form
                                {...TelegramController.store.form()}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="generate-telegram-code-button"
                                    >
                                        Generate pairing code
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

Telegram.layout = {
    breadcrumbs: [
        {
            title: 'Telegram settings',
            href: edit(),
        },
    ],
};
