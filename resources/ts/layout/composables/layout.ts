import { UnwrapNestedRefs, computed, reactive, readonly } from 'vue'

const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'emerald',
    surface: null,
    darkTheme: false,
    menuMode: 'static',
})

interface LayoutState {
    staticMenuDesktopInactive: boolean
    overlayMenuActive: boolean
    profileSidebarVisible: boolean
    configSidebarVisible: boolean
    staticMenuMobileActive: boolean
    menuHoverActive: boolean
    activeMenuItem: string | null
}

const layoutState: UnwrapNestedRefs<LayoutState> = reactive({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    activeMenuItem: null,
})

export function useLayout() {
    const setPrimary = (value: string): void => {
        layoutConfig.primary = value
    }

    const setSurface = (value: string | null): void => {
        layoutConfig.surface = value
    }

    const setPreset = (value: string): void => {
        layoutConfig.preset = value
    }

    const setActiveMenuItem = (item: string): void => {
        layoutState.activeMenuItem = item
    }

    const setMenuMode = (mode: string): void => {
        layoutConfig.menuMode = mode
    }

    const toggleDarkMode = (): void => {
        if (!document.startViewTransition) {
            executeDarkModeToggle()

            return
        }

        document.startViewTransition(() => executeDarkModeToggle())
    }

    const executeDarkModeToggle = (): void => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme
        document.documentElement.classList.toggle('app-dark')
    }

    const onMenuToggle = (): void => {
        if (layoutConfig.menuMode === 'overlay') {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive
        } else {
            layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive
        }
    }

    const resetMenu = (): void => {
        layoutState.overlayMenuActive = false
        layoutState.staticMenuMobileActive = false
        layoutState.menuHoverActive = false
    }

    const isSidebarActive = computed<boolean>((): boolean => !!(layoutState.overlayMenuActive || layoutState.staticMenuMobileActive))

    const isDarkTheme = computed<boolean>(() => layoutConfig.darkTheme)

    const getPrimary = computed<string>(() => layoutConfig.primary)

    const getSurface = computed<string>(() => layoutConfig.surface)

    return { layoutConfig: readonly(layoutConfig), layoutState: readonly(layoutState), onMenuToggle, isSidebarActive, isDarkTheme, getPrimary, getSurface, setActiveMenuItem, toggleDarkMode, setPrimary, setSurface, setPreset, resetMenu, setMenuMode }
}
