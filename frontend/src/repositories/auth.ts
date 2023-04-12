type Login = {
    email: string,
    password: string,
    rememberMe: boolean
}

type LoginBody = {
    email: string,
    password: string,
    rememberMe?: string
}

export const login = async ({rememberMe, ...login}: Login) => {
    const body: LoginBody = {
        ...login
    }
    if (rememberMe) {
        body.rememberMe = '1'        
    }

    await fetch('/login', {
        method: 'POST',
        headers:{
            'Content-Type': 'application/x-www-form-urlencoded'
        },    
        body: new URLSearchParams(body)
    });
}