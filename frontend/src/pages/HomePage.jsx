import { useEffect, useState } from 'react'
import './HomePage.css'

export default function HomePage() {
  const [health, setHealth] = useState(null)

  useEffect(() => {
    fetch('/api/health')
      .then((r) => r.json())
      .then(setHealth)
      .catch(() => setHealth({ error: 'API indisponível (inicie o backend na porta 3000)' }))
  }, [])

  return (
    <div className="home-page">
      <section className="home-page__hero" aria-labelledby="home-titulo">
        <h2 id="home-titulo" className="home-page__title">
          Painel NPS
        </h2>
        <p className="home-page__lead">
          Use o menu à esquerda para navegar. O conteúdo de cada tela aparece nesta área.
        </p>
        <p className="home-page__health" aria-live="polite">
          <strong>API</strong>:{' '}
          {health === null && 'carregando…'}
          {health?.data?.ok === true && `ok — ${health.data.ts}`}
          {health?.error != null && String(health.error)}
        </p>
      </section>
    </div>
  )
}
