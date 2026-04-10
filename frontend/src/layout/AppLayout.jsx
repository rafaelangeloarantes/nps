import { useCallback, useEffect, useMemo, useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import './AppLayout.css'

const SIDEBAR_EXPANDED = 260
const SIDEBAR_COLLAPSED = 72
const MQ_MOBILE = '(max-width: 768px)'

function IconHome(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden {...props}>
      <path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5z" strokeLinejoin="round" />
    </svg>
  )
}

function IconChart(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden {...props}>
      <path d="M4 19V5M4 19h16M8 17V9m4 8V7m4 10v-4" strokeLinecap="round" />
    </svg>
  )
}

function IconChevronLeft(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden {...props}>
      <path d="M15 6l-6 6 6 6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

function IconChevronRight(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden {...props}>
      <path d="M9 6l6 6-6 6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

function IconPin(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden {...props}>
      <path d="M12 17v5M9 8l-3 9h12l-3-9H9zM12 2v6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

function IconMenu(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden {...props}>
      <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />
    </svg>
  )
}

const NAV = [
  { to: '/', end: true, label: 'Início', Icon: IconHome },
  { to: '/pesquisas', label: 'Pesquisas', Icon: IconChart },
]

function useMediaQuery(query) {
  const [matches, setMatches] = useState(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return false
    return window.matchMedia(query).matches
  })

  useEffect(() => {
    const mq = window.matchMedia(query)
    const onChange = () => setMatches(mq.matches)
    onChange()
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [query])

  return matches
}

export default function AppLayout() {
  const location = useLocation()
  const isMobile = useMediaQuery(MQ_MOBILE)

  const [collapsed, setCollapsed] = useState(false)
  const [pinned, setPinned] = useState(true)
  const [hoverExpand, setHoverExpand] = useState(false)
  const [drawerOpen, setDrawerOpen] = useState(false)

  const showLabels = isMobile || !collapsed || (collapsed && !pinned && hoverExpand)

  const sidebarWidth = useMemo(() => {
    if (isMobile) return SIDEBAR_EXPANDED
    if (!collapsed) return SIDEBAR_EXPANDED
    if (!pinned && hoverExpand) return SIDEBAR_EXPANDED
    return SIDEBAR_COLLAPSED
  }, [isMobile, collapsed, pinned, hoverExpand])

  const shellClass =
    'app-shell' + (isMobile && drawerOpen ? ' app-shell--drawer-open' : '')

  const closeDrawer = useCallback(() => setDrawerOpen(false), [])

  const headerTitle = useMemo(() => {
    const item = NAV.find((n) => (n.end ? location.pathname === n.to : location.pathname.startsWith(n.to)))
    if (item) return item.label
    if (location.pathname === '/') return 'Início'
    return 'NPS'
  }, [location.pathname])

  const onNavClick = () => {
    if (isMobile) closeDrawer()
  }

  const toggleCollapsed = () => {
    setCollapsed((c) => !c)
    setHoverExpand(false)
  }

  const togglePinned = () => setPinned((p) => !p)

  const sidebarStyle = { '--sidebar-w': `${sidebarWidth}px` }

  return (
    <div className={shellClass}>
      {isMobile && (
        <button
          type="button"
          className="app-shell__backdrop"
          aria-label="Fechar menu"
          onClick={closeDrawer}
        />
      )}

      <aside
        id="app-sidebar"
        className={
          'app-shell__sidebar' +
          (!isMobile && !showLabels ? ' app-shell__sidebar--compact' : '')
        }
        style={sidebarStyle}
        onMouseEnter={() => {
          if (!isMobile && collapsed && !pinned) setHoverExpand(true)
        }}
        onMouseLeave={() => {
          if (!isMobile && collapsed && !pinned) setHoverExpand(false)
        }}
        aria-label="Menu principal"
      >
        <div className="app-shell__sidebar-inner">
          <div className="app-shell__brand">
            <span className="app-shell__brand-mark" aria-hidden />
            <span className="app-shell__brand-text">NPS</span>
          </div>

          <nav className="app-shell__nav" aria-label="Navegação">
            {NAV.map((item) => {
              const NavIcon = item.Icon
              return (
                <NavLink key={item.to} to={item.to} end={item.end} onClick={onNavClick}>
                  <NavIcon className="app-shell__nav-icon" />
                  <span className="app-shell__nav-label">{item.label}</span>
                </NavLink>
              )
            })}
          </nav>

          {!isMobile && (
            <div className="app-shell__sidebar-footer">
              <button
                type="button"
                className="app-shell__icon-btn"
                onClick={toggleCollapsed}
                title={collapsed ? 'Expandir menu' : 'Recolher menu'}
                aria-expanded={!collapsed}
              >
                {collapsed ? <IconChevronRight /> : <IconChevronLeft />}
                <span className="app-shell__icon-btn-label">
                  {collapsed ? 'Expandir' : 'Recolher'}
                </span>
              </button>
              <button
                type="button"
                className="app-shell__icon-btn"
                onClick={togglePinned}
                aria-pressed={pinned}
                title={
                  pinned
                    ? 'Destravar: ao recolher, o menu expande ao passar o mouse'
                    : 'Travar: fixa largura recolhida sem expandir ao passar o mouse'
                }
              >
                <IconPin />
                <span className="app-shell__icon-btn-label">
                  {pinned ? 'Travado' : 'Destravado'}
                </span>
              </button>
            </div>
          )}
        </div>
      </aside>

      <div className="app-shell__main-wrap">
        <header className="app-shell__header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
            {isMobile && (
              <button
                type="button"
                className="app-shell__menu-toggle"
                aria-expanded={drawerOpen}
                aria-controls="app-sidebar"
                onClick={() => setDrawerOpen((o) => !o)}
              >
                <IconMenu />
              </button>
            )}
            <h1 className="app-shell__header-title">{headerTitle}</h1>
          </div>
          <div className="app-shell__header-actions" />
        </header>

        <main className="app-shell__main" id="conteudo-principal">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
