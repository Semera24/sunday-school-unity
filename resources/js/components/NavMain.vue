<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useT } from '@/composables/useT';
import type { NavGroup } from '@/types';

defineProps<{
    items: NavGroup[];
    openGroups: Record<string, boolean>;
}>();

const emit = defineEmits<{
    toggleGroup: [title: string];
}>();

const t = useT();
const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <template v-for="(group, index) in items" :key="group.title">
        <SidebarSeparator v-if="index > 0" class="mx-2 my-3" />
        <Collapsible :open="openGroups[group.title]">
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>{{ group.labelKey ? t(group.labelKey) : group.title }}</SidebarGroupLabel>
                <CollapsibleTrigger as-child>
                    <SidebarGroupAction
                        :aria-label="t(openGroups[group.title] ? 'common.collapse' : 'common.expand', { name: group.labelKey ? t(group.labelKey) : group.title })"
                        @click="emit('toggleGroup', group.title)"
                    >
                        <ChevronDown
                            class="transition-transform duration-200"
                            :class="
                                openGroups[group.title]
                                    ? 'rotate-0'
                                    : '-rotate-90'
                            "
                        />
                    </SidebarGroupAction>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in group.items"
                            :key="`${group.title}-${item.title}`"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="isCurrentUrl(item.href)"
                                :tooltip="item.labelKey ? t(item.labelKey) : item.title"
                            >
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.labelKey ? t(item.labelKey) : item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </CollapsibleContent>
            </SidebarGroup>
        </Collapsible>
    </template>
</template>
