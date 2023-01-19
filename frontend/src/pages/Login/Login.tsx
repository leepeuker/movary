import { Button, Checkbox, Stack, TextInput, Title } from "@mantine/core"
import { useForm } from "@mantine/form";

const Login = () => {
    const form = useForm({
        initialValues: {
            email: '',
            password: '',
            rememberme: false,
        },

        validate: {
            email: (value) => (/^\S+@\S+$/.test(value) ? null : 'Invalid email'),
        },
    });

    return (
        <Stack style={{height: "100%"}} justify="center" align="center">
            <Title order={1}>Movary</Title>
            <form onSubmit={form.onSubmit((values) => console.log(values))} style={{display: 'inline-block'}}>
                <TextInput
                    size="lg"
                    label="Email"
                    {...form.getInputProps('email')}
                    />
                <TextInput
                    mt="lg" 
                    size="lg"
                    label="Password"
                    type="password"
                    {...form.getInputProps('password')}
                />
                <Checkbox
                    mt="lg"
                    label="Remember me"
                    {...form.getInputProps('rememberme', { type: 'checkbox' })}
                />
                <Button size="lg" mt="lg" fullWidth type="submit">Sign in</Button>
            </form>
        </Stack>
    )
}

export default Login
