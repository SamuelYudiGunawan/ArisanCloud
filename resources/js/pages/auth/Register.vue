<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { LogIn } from 'lucide-vue-next';
</script>

<template>
    <AuthBase
        title="Selamat Datang"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="name" class="text-gray-700">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Input your name"
                        class="border-[#1e3a5f] focus:ring-[#1e3a5f]"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-gray-700">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="Janedoe@email.com"
                        class="border-[#1e3a5f] focus:ring-[#1e3a5f]"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-gray-700">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="password"
                        class="border-[#1e3a5f] focus:ring-[#1e3a5f]"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-gray-700">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="password"
                        class="border-[#1e3a5f] focus:ring-[#1e3a5f]"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-2 text-gray-600">
                        <Checkbox id="remember" name="remember" :tabindex="5" />
                        <span class="text-sm">Ingat Saya</span>
                    </Label>
                    <TextLink
                        :href="request()"
                        class="text-sm text-[#1e3a5f] hover:underline"
                        :tabindex="7"
                    >
                        Lupa Password?
                    </TextLink>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full bg-[#1e3a5f] hover:bg-[#152a45] text-white"
                    tabindex="6"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    <LogIn v-else class="w-4 h-4 mr-2" />
                    Masuk
                </Button>
            </div>

            <div class="text-center text-sm text-gray-500">
                Sudah punya akun?
                <TextLink
                    :href="login()"
                    class="text-[#1e3a5f] hover:underline font-medium"
                    :tabindex="8"
                >
                    Login
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
