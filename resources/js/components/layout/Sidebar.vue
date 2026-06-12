<template>

  <nav class="px-3 py-4 space-y-1.5">

    <template v-for="item in visibleItems" :key="item.label">

      <div v-if="item.children?.length">

        <button

          type="button"

          class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 transition-colors"

          :class="[

            getMenuTheme(item).group,

            openGroups[item.label] ? getMenuTheme(item).groupOpen : '',

            groupHasActiveChild(item) ? 'ring-1 ring-inset ring-black/5 dark:ring-white/10' : '',

          ]"

          @click="toggleGroup(item.label)"

        >

          <span class="flex items-center gap-3 min-w-0">

            <span

              class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-sm"

              :class="getMenuTheme(item).badge"

            >

              <AppIcon :name="item.icon" class-name="w-5 h-5" />

            </span>

            <span class="truncate">{{ item.label }}</span>

          </span>

          <svg

            :class="['w-4 h-4 shrink-0 text-gray-400 transition-transform', openGroups[item.label] ? 'rotate-180' : '']"

            fill="none"

            stroke="currentColor"

            viewBox="0 0 24 24"

          >

            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />

          </svg>

        </button>



        <div

          v-show="openGroups[item.label]"

          class="mt-1.5 ml-5 pl-3 space-y-1 border-l-2"

          :class="getMenuTheme(item).rail"

        >

          <RouterLink

            v-for="child in item.children"

            :key="child.to"

            :to="child.to"

            class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 transition-colors"

            :class="[

              getMenuTheme(child, item).childHover,

              isChildActive(child) ? `${getMenuTheme(child, item).childActive} font-medium` : '',

            ]"

            @click="$emit('navigate')"

          >

            <span

              class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"

              :class="getMenuTheme(child, item).childBadge"

            >

              <AppIcon :name="child.icon || item.icon" class-name="w-4 h-4" />

            </span>

            <span class="truncate">{{ child.label }}</span>

          </RouterLink>

        </div>

      </div>



      <RouterLink

        v-else

        :to="item.to"

        class="flex items-center gap-3 px-2.5 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 transition-colors"

        :class="[

          getMenuTheme(item).leafHover,

          isLeafActive(item) ? getMenuTheme(item).leafActive : '',

        ]"

        @click="$emit('navigate')"

      >

        <span

          class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-sm"

          :class="getMenuTheme(item).badge"

        >

          <AppIcon :name="item.icon" class-name="w-5 h-5" />

        </span>

        <span class="truncate">{{ item.label }}</span>

      </RouterLink>

    </template>

  </nav>

</template>



<script setup>

import { computed, reactive, watch } from 'vue';

import { useRoute } from 'vue-router';

import { useAuthStore } from '@/stores/auth';

import { sidebarMenu } from '@/config/sidebarMenu';

import { filterMenuByPermissions } from '@/utils/permissions';

import { getMenuTheme, isMenuPathActive } from '@/utils/sidebarMenuTheme';

import AppIcon from '@/components/ui/AppIcon.vue';



defineEmits(['navigate']);



const authStore = useAuthStore();

const route = useRoute();

const openGroups = reactive({});



const visibleItems = computed(() =>

  filterMenuByPermissions(sidebarMenu, authStore.user)

);



function isChildActive(child) {

  return isMenuPathActive(route.path, child.to);

}



function isLeafActive(item) {

  return isMenuPathActive(route.path, item.to);

}



function groupHasActiveChild(item) {

  return item.children?.some((child) => isChildActive(child));

}



function toggleGroup(label) {

  openGroups[label] = !openGroups[label];

}



function syncOpenGroups() {

  visibleItems.value

    .filter((item) => item.children?.length)

    .forEach((item) => {

      if (openGroups[item.label] === undefined || groupHasActiveChild(item)) {

        openGroups[item.label] = true;

      }

    });

}



watch(visibleItems, syncOpenGroups, { immediate: true });

watch(() => route.path, syncOpenGroups);

</script>


