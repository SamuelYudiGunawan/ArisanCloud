<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { LogIn } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Selamat Datang"
    >
        <Head title="Login" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="text-gray-700">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
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
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="password"
                        class="border-[#1e3a5f] focus:ring-[#1e3a5f]"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-2 text-gray-600">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span class="text-sm">Ingat Saya</span>
                    </Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm text-[#1e3a5f] hover:underline"
                        :tabindex="5"
                    >
                        Lupa Password?
                    </TextLink>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full bg-[#1e3a5f] hover:bg-[#152a45] text-white"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    <LogIn v-else class="w-4 h-4 mr-2" />
                    Masuk
                </Button>
            </div>

            <div
                class="text-center text-sm text-gray-500"
                v-if="canRegister"
            >
                Belum punya akun?
                <TextLink :href="register()" :tabindex="6" class="text-[#1e3a5f] hover:underline font-medium">
                    Daftar
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
