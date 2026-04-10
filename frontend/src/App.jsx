import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import AppLayout from './layout/AppLayout.jsx'
import HomePage from './pages/HomePage.jsx'
import PesquisasPage from './pages/PesquisasPage.jsx'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<AppLayout />}>
          <Route index element={<HomePage />} />
          <Route path="pesquisas" element={<PesquisasPage />} />
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
