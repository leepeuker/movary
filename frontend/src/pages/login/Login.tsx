import { Button, Checkbox, Stack, TextInput, Title } from "@mantine/core"
import { useForm } from "@mantine/form";
import { useEffect } from "react";
import { useTranslation } from "react-i18next";
import { useMutation, useQueryClient } from "react-query";
import { login } from "../../repositories/auth";

const Login = () => {
    const {i18n, t} = useTranslation('login');

    const form = useForm({
        initialValues: {
            email: '',
            password: '',
            rememberMe: false,
        },

        validate: {
            email: (value) => (/^\S+@\S+$/.test(value) ? null : 'Invalid email'),
        },
    });

    const queryClient = useQueryClient()
    const mutation = useMutation(login, {
        onSuccess: () => {
            queryClient.invalidateQueries('user')
        },
    })

    return (
        <Stack style={{ height: "100%" }} justify="center" align="center">
            <Title order={1}>Movary</Title>
            <form onSubmit={form.onSubmit((values) => mutation.mutate(values))} style={{ display: 'inline-block' }}>
                <TextInput
                    size="lg"
                    label={t('email')}
                    {...form.getInputProps('email')}
                />
                <TextInput
                    mt="lg"
                    size="lg"
                    label={t('password')}
                    type="password"
                    {...form.getInputProps('password')}
                />
                <Checkbox
                    mt="lg"
                    label={t('remember')}
                    {...form.getInputProps('rememberme', { type: 'checkbox' })}
                />
                <Button size="lg" mt="lg" loading={mutation.isLoading} fullWidth type="submit">Sign in</Button>
            </form>
        </Stack>
    )
}

export default Login
