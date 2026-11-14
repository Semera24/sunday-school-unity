import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type Translations = { [key: string]: string | Translations };

/**
 * Access shared translations exposed by HandleInertiaRequests.
 *
 * Usage: const t = useT(); t('common.save') → "Save"
 * English fallback is merged by the server; missing keys remain visible.
 */
export function useT() {
    const page = usePage<{ translations: Translations }>();

    const translations = computed<Translations>(
        () => page.props.translations ?? {},
    );

    return (key: string, parameters: Record<string, string | number> = {}): string => {
        const value = key
            .split('.')
            .reduce<
                Translations | string | undefined
            >((carry, segment) => (carry && typeof carry === 'object' ? carry[segment] : undefined), translations.value);

        let message = typeof value === 'string' ? value : key;
        for (const [name, replacement] of Object.entries(parameters)) {
            message = message.split(`:${name}`).join(String(replacement));
        }
        return message;
    };
}
