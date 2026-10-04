<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import Aviso from '@/Components/Aviso.vue';
import Campo from '@/Components/Campo.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Información del perfil
        </template>

        <template #description>
            Actualiza tu nombre y tu correo electrónico.
        </template>

        <template #form>
            <!-- Foto de perfil -->
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Foto" />

                <div v-show="! photoPreview" class="mt-2">
                    <img :src="user.profile_photo_url" :alt="user.name" class="h-20 w-20 rounded-full object-cover">
                </div>

                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
                        :style="{ backgroundImage: `url('${photoPreview}')` }"
                    />
                </div>

                <div class="mt-3 flex flex-wrap gap-3">
                    <SecondaryButton type="button" @click.prevent="selectNewPhoto">
                        Elegir otra foto
                    </SecondaryButton>

                    <SecondaryButton
                        v-if="user.profile_photo_path"
                        type="button"
                        @click.prevent="deletePhoto"
                    >
                        Quitar foto
                    </SecondaryButton>
                </div>

                <InputError :message="form.errors.photo" class="mt-1.5" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <Campo id="name" v-slot="campo" etiqueta="Nombre" :error="form.errors.name">
                    <TextInput
                        :id="campo.id"
                        v-model="form.name"
                        type="text"
                        class="block w-full"
                        :invalido="campo.invalido"
                        :aria-describedby="campo.describedby"
                        required
                        autocomplete="name"
                    />
                </Campo>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <Campo id="email" v-slot="campo" etiqueta="Correo electrónico" :error="form.errors.email">
                    <TextInput
                        :id="campo.id"
                        v-model="form.email"
                        type="email"
                        inputmode="email"
                        class="block w-full"
                        :invalido="campo.invalido"
                        :aria-describedby="campo.describedby"
                        required
                        autocomplete="username"
                    />
                </Campo>

                <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null" class="mt-3">
                    <p class="text-small text-ink-600">
                        Tu correo todavía no está verificado.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="rounded-sm text-action underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                            @click.prevent="sendEmailVerification"
                        >
                            Reenviar el correo de verificación.
                        </Link>
                    </p>

                    <Aviso v-show="verificationLinkSent" tipo="exito" class="mt-2">
                        Enviamos un enlace de verificación nuevo a tu correo.
                    </Aviso>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful">
                Cambios guardados.
            </ActionMessage>

            <PrimaryButton :procesando="form.processing">
                {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
