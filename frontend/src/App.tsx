import { MantineProvider } from '@mantine/core'
import Login from './pages/Login/Login'

const App = () => {
  return (
    <MantineProvider withNormalizeCSS withGlobalStyles>
      <Login />
    </MantineProvider>
  )
}

export default App
