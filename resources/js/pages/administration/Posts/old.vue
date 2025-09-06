    <!-- Archivos Multimedia -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">Archivos Multimedia</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Imagen -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Imagen Principal
                                </label>
                                
                                <div v-if="!imagePreview" 
                                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors cursor-pointer"
                                     @dragover="handleImageDragOver"
                                     @drop="handleImageDrop"
                                     @click="imageInputRef?.click()"
                                >
                                    <ImageIcon class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium text-blue-600 dark:text-blue-400">Haz clic para subir</span>
                                            o arrastra y suelta
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            PNG, JPG, GIF hasta 2MB
                                        </p>
                                    </div>
                                </div>
                                
                                <div v-else class="relative">
                                    <img :src="imagePreview" alt="Preview" class="w-full h-48 object-cover rounded-lg" />
                                    <button
                                        @click="removeImage"
                                        type="button"
                                        class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-lg"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                                
                                <input
                                    ref="imageInputRef"
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageUpload"
                                    class="hidden"
                                />
                                
                                <div v-if="form.errors.image" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.image }}
                                </div>
                            </div>

                            <!-- Archivo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Archivo Adjunto
                                </label>
                                
                                <div v-if="!filePreview" 
                                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors cursor-pointer"
                                     @click="fileInputRef?.click()"
                                >
                                    <Upload class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium text-blue-600 dark:text-blue-400">Haz clic para subir archivo</span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            Cualquier tipo de archivo hasta 10MB
                                        </p>
                                    </div>
                                </div>
                                
                                <div v-else class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <FileText class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ filePreview.name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ filePreview.size }}</p>
                                            </div>
                                        </div>
                                        <button
                                            @click="removeFile"
                                            type="button"
                                            class="text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <X class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                                
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    @change="handleFileUpload"
                                    class="hidden"
                                />
                                
                                <div v-if="form.errors.file" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.file }}
                                </div>
                            </div>
                        </div>
                    </div>