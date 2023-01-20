import { MantineProvider } from '@mantine/core'
import Login from './pages/Login/Login'
import { Suspense } from 'react'
import Loader from './components/Loader'
import { QueryClient, QueryClientProvider } from 'react-query'
import { createBrowserRouter, RouterProvider } from 'react-router-dom'

const App = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        suspense: true,
      },
    },
  })

  const router = createBrowserRouter([
    {
      path: "/",
      element: <Login />,
    },
  ]);

  return (
    <MantineProvider withNormalizeCSS withGlobalStyles>
      <Suspense fallback={<Loader/>}>
        <QueryClientProvider client={queryClient}>
          <RouterProvider router={router} />
        </QueryClientProvider>
      </Suspense>
    </MantineProvider>
  )
}

export default App
