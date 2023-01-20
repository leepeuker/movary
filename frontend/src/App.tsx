import { MantineProvider } from '@mantine/core'
import Login from './pages/Login/Login'
import { Suspense } from 'react'
import Loader from './components/Loader'
import { QueryClient, QueryClientProvider } from 'react-query'

const App = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        suspense: true,
      },
    },
  })

  return (
    <MantineProvider withNormalizeCSS withGlobalStyles>
      <Suspense fallback={<Loader/>}>
        <QueryClientProvider client={queryClient}>
          <Login />
        </QueryClientProvider>
      </Suspense>
    </MantineProvider>
  )
}

export default App
