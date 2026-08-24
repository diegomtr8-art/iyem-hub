<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    nombre_completo: '',
    email: '',
    telefono: '',
    curp: '',
    rfc: '',
    calle: '',
    codigo_postal: '',
    municipio: '',
    estado: 'Yucatán',
    tipo_persona: 'fisica',
    estado_persona: 'activa',
});

const submit = () => {
    form.post(route('padron.store'));
};
</script>

<template>
    <AppLayout title="Nuevo contacto">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nuevo contacto
            </h2>
        </template>

        <div class="max-w-xl rounded-2xl border border-iyem-200 bg-white p-5 shadow-soft sm:p-8">
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="nombre_completo" value="Nombre completo" />
                    <TextInput id="nombre_completo" v-model="form.nombre_completo" class="mt-1 block w-full" required autofocus />
                    <InputError class="mt-2" :message="form.errors.nombre_completo" />
                </div>

                <div>
                    <InputLabel for="email" value="Correo electrónico" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        inputmode="email"
                        autocomplete="email"
                        autocapitalize="none"
                        spellcheck="false"
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="telefono" value="Teléfono" />
                    <TextInput
                        id="telefono"
                        v-model="form.telefono"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="tel-national"
                        maxlength="10"
                        placeholder="9991234567"
                        class="mt-1 block w-full tabular-nums"
                    />
                    <InputError class="mt-2" :message="form.errors.telefono" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="curp" value="CURP" />
                        <TextInput
                            id="curp"
                            v-model="form.curp"
                            maxlength="18"
                            autocapitalize="characters"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="18 caracteres"
                            class="mt-1 block w-full uppercase"
                        />
                        <InputError class="mt-2" :message="form.errors.curp" />
                    </div>
                    <div>
                        <InputLabel for="rfc" value="RFC" />
                        <TextInput
                            id="rfc"
                            v-model="form.rfc"
                            maxlength="13"
                            autocapitalize="characters"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="12 o 13 caracteres"
                            class="mt-1 block w-full uppercase"
                        />
                        <InputError class="mt-2" :message="form.errors.rfc" />
                    </div>
                </div>

                <div>
                    <InputLabel for="calle" value="Calle y número" />
                    <TextInput id="calle" v-model="form.calle" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.calle" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="municipio" value="Municipio" />
                        <TextInput id="municipio" v-model="form.municipio" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.municipio" />
                    </div>
                    <div>
                        <InputLabel for="codigo_postal" value="Código postal" />
                        <TextInput
                            id="codigo_postal"
                            v-model="form.codigo_postal"
                            inputmode="numeric"
                            autocomplete="postal-code"
                            maxlength="5"
                            class="mt-1 block w-full tabular-nums"
                        />
                        <InputError class="mt-2" :message="form.errors.codigo_postal" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="tipo_persona" value="Tipo de persona" />
                        <select
                            id="tipo_persona"
                            v-model="form.tipo_persona"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                        >
                            <option value="fisica">Física</option>
                            <option value="moral">Moral</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.tipo_persona" />
                    </div>
                    <div>
                        <InputLabel for="estado_persona" value="Estado" />
                        <select
                            id="estado_persona"
                            v-model="form.estado_persona"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                        >
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                            <option value="bloqueada">Bloqueada</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.estado_persona" />
                    </div>
                </div>

                <PrimaryButton :disabled="form.processing">
                    Guardar contacto
                </PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
