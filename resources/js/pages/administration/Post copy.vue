<!-- <script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

// Icons
import {
  Plus,
  Edit,
  Trash2,
  Eye,
  Search,
  Filter,
  FileText,
  Image as ImageIcon,
  File,
  Loader2,
  MoreHorizontal,
  CheckCircle,
  ExternalLink,
} from 'lucide-vue-next';

// Types
interface Post {
  id: number;
  title: string;
  content: string;
  Category: string;
  status: 'draft' | 'published' | 'delete';
  image_path?: string;
  file_path?: string;
  Subscripcion: string[];
  created_at: string;
  updated_at: string;
  image_url?: string;
  file_url?: string;
}

interface Props {
  posts?: {
    data: Post[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  filters?: {
    category?: string;
    status?: string;
    search?: string;
  };
}

const props = withDefaults(defineProps<Props>(), {
  posts: () => ({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 }),
  filters: () => ({})
});

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Publicaciones',
    href: route('posts.index'),
  },
];

// Estados reactivos
const posts = ref(props.posts);
const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const selectedStatus = ref(props.filters?.status || '');

// Estados del modal
const showCreateModal = ref(false);
const editingPost = ref<Post | null>(null);
const isSubmitting = ref(false);
const imagePreview = ref('');
const selectedFile = ref<File | null>(null);
const showDeleteDialog = ref(false);
const postToDelete = ref<Post | null>(null);
const showViewModal = ref(false);
const viewingPost = ref<Post | null>(null);

// Formulario reactivo
const postForm = reactive({
  title: '',
  content: '',
  Category: '',
  status: 'draft' as 'draft' | 'published' | 'delete',
  Subscripcion: [] as string[],
  image: null as File | null,
  file: null as File | null
});

// Errores de validación
const errors = ref<Record<string, string>>({});

// Constantes
const categories = ['RRHH', 'LEGAL', 'GENERAL'];
const statuses = [
  { value: 'draft', label: 'Borrador', color: 'bg-gray-100 text-gray-800' },
  { value: 'published', label: 'Publicado', color: 'bg-green-100 text-green-800' },
  { value: 'delete', label: 'Eliminado', color: 'bg-red-100 text-red-800' }
];

// Funciones de utilidad
const getStatusLabel = (status: string) => {
  const statusObj = statuses.find(s => s.value === status);
  return statusObj?.label || status;
};

const getStatusColor = (status: string) => {
  const statusObj = statuses.find(s => s.value === status);
  return statusObj?.color || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Funciones del modal
const openCreateModal = () => {
  resetForm();
  editingPost.value = null;
  showCreateModal.value = true;
};

const openEditModal = (post: Post) => {
  editingPost.value = post;
  postForm.title = post.title;
  postForm.content = post.content;
  postForm.Category = post.Category;
  postForm.status = post.status;
  postForm.Subscripcion = Array.isArray(post.Subscripcion) ? post.Subscripcion : [];
  
  if (post.image_url) {
    imagePreview.value = post.image_url;
  }
  
  postForm.image = null;
  postForm.file = null;
  selectedFile.value = null;
  
  showCreateModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  resetForm();
  errors.value = {};
};

const resetForm = () => {
  Object.assign(postForm, {
    title: '',
    content: '',
    Category: '',
    status: 'draft',
    Subscripcion: [],
    image: null,
    file: null
  });
  imagePreview.value = '';
  selectedFile.value = null;
};

// Manejo de suscripciones
const toggleSubscription = (subscription: string) => {
  const index = postForm.Subscripcion.indexOf(subscription);
  if (index > -1) {
    postForm.Subscripcion.splice(index, 1);
  } else {
    postForm.Subscripcion.push(subscription);
  }
};

// Manejo de archivos
const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    postForm.image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    postForm.file = file;
    selectedFile.value = file;
  }
};

const removeImage = () => {
  postForm.image = null;
  imagePreview.value = '';
};

const removeFile = () => {
  postForm.file = null;
  selectedFile.value = null;
};

// Envío del formulario
const submitPost = async () => {
  isSubmitting.value = true;
  errors.value = {};
  
  try {
    const formData = new FormData();
    formData.append('title', postForm.title);
    formData.append('content', postForm.content);
    formData.append('Category', postForm.Category);
    formData.append('status', postForm.status);
    formData.append('Subscripcion', JSON.stringify(postForm.Subscripcion));
    
    if (postForm.image) {
      formData.append('image', postForm.image);
    }
    
    if (postForm.file) {
      formData.append('file', postForm.file);
    }

    if (editingPost.value) {
      formData.append('_method', 'PUT');
      router.post(route('posts.update', editingPost.value.id), formData, {
        onSuccess: () => {
          closeModal();
          loadPosts();
        },
        onError: (errors) => {
          errors.value = errors;
        }
      });
    } else {
      router.post(route('posts.store'), formData, {
        onSuccess: () => {
          closeModal();
          loadPosts();
        },
        onError: (errors) => {
          errors.value = errors;
        }
      });
    }
    
  } catch (error) {
    console.error('Error al enviar formulario:', error);
  } finally {
    isSubmitting.value = false;
  }
};

// Función para ver post
const viewPost = (post: Post) => {
  viewingPost.value = post;
  showViewModal.value = true;
};

// Función para cambiar estado
const changePostStatus = async (postId: number, newStatus: string) => {
  router.patch(route('posts.changeStatus', postId), {
    status: newStatus
  }, {
    onSuccess: () => {
      loadPosts();
    }
  });
};

// Funciones de eliminación
const confirmDelete = (post: Post) => {
  postToDelete.value = post;
  showDeleteDialog.value = true;
};

const deletePost = async () => {
  if (!postToDelete.value) return;
  
  router.delete(route('posts.destroy', postToDelete.value.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
      postToDelete.value = null;
      loadPosts();
    }
  });
};

// Funciones de filtrado y búsqueda
const loadPosts = () => {
  const params: Record<string, string> = {};
  
  if (searchQuery.value) params.search = searchQuery.value;
  if (selectedCategory.value) params.category = selectedCategory.value;
  if (selectedStatus.value) params.status = selectedStatus.value;
  
  router.get(route('posts.index'), params, {
    preserveState: true,
    replace: true,
    onSuccess: (page) => {
      posts.value = page.props.posts;
    }
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = '';
  selectedStatus.value = '';
  loadPosts();
};

// Paginación
const goToPage = (page: number) => {
  const params: Record<string, string | number> = { page };
  
  if (searchQuery.value) params.search = searchQuery.value;
  if (selectedCategory.value) params.category = selectedCategory.value;
  if (selectedStatus.value) params.status = selectedStatus.value;
  
  router.get(route('posts.index'), params, {
    preserveState: true,
    replace: true,
    onSuccess: (page) => {
      posts.value = page.props.posts;
    }
  });
};

// Watchers para aplicar filtros automáticamente
watch([searchQuery, selectedCategory, selectedStatus], () => {
  loadPosts();
}, { debounce: 300 });
</script>

<template>
  <Head title="Publicaciones" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
     
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Publicaciones</h1>
          <p class="text-gray-600 dark:text-gray-400">Administra todas las publicaciones del sistema</p>
        </div>
        <Button @click="openCreateModal" class="flex items-center gap-2">
          <Plus class="h-4 w-4" />
          Nueva Publicación
        </Button>
      </div>

     
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Filtros y Búsqueda
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <Input
                v-model="searchQuery"
                placeholder="Buscar por título..."
                class="pl-10"
              />
            </div>
            <Select v-model="selectedCategory">
              <SelectTrigger>
                <SelectValue placeholder="Todas las categorías" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">Todas las categorías</SelectItem>
                <SelectItem v-for="category in categories" :key="category" :value="category">
                  {{ category }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Select v-model="selectedStatus">
              <SelectTrigger>
                <SelectValue placeholder="Todos los estados" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">Todos los estados</SelectItem>
                <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
                  {{ status.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button @click="clearFilters" variant="outline">
              Limpiar Filtros
            </Button>
          </div>
        </CardContent>
      </Card>

  
      <Card>
        <CardHeader>
          <CardTitle>Publicaciones ({{ posts.total }} total)</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Título</TableHead>
                  <TableHead>Categoría</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead>Suscripciones</TableHead>
                  <TableHead>Archivos</TableHead>
                  <TableHead>Fecha</TableHead>
                  <TableHead class="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="post in posts.data" :key="post.id">
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ post.title }}</div>
                      <div class="text-sm text-gray-500 truncate max-w-xs">
                        {{ post.content.substring(0, 100) }}{{ post.content.length > 100 ? '...' : '' }}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">{{ post.Category }}</Badge>
                  </TableCell>
                  <TableCell>
                    <Badge :class="getStatusColor(post.status)">
                      {{ getStatusLabel(post.status) }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex flex-wrap gap-1">
                      <Badge 
                        v-for="sub in post.Subscripcion" 
                        :key="sub"
                        variant="secondary"
                        class="text-xs"
                      >
                        {{ sub }}
                      </Badge>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div class="flex gap-2">
                      <div v-if="post.image_path" class="flex items-center gap-1 text-xs text-gray-500">
                        <ImageIcon class="h-3 w-3" />
                        Img
                      </div>
                      <div v-if="post.file_path" class="flex items-center gap-1 text-xs text-gray-500">
                        <File class="h-3 w-3" />
                        File
                      </div>
                    </div>
                  </TableCell>
                  <TableCell class="text-sm text-gray-500">
                    {{ formatDate(post.created_at) }}
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <Button 
                        variant="ghost" 
                        size="sm" 
                        @click="viewPost(post)"
                        class="h-8 w-8 p-0"
                      >
                        <Eye class="h-4 w-4" />
                      </Button>
                      
                      <Button 
                        variant="ghost" 
                        size="sm" 
                        @click="openEditModal(post)"
                        class="h-8 w-8 p-0"
                      >
                        <Edit class="h-4 w-4" />
                      </Button>
                      
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                            <MoreHorizontal class="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuLabel>Cambiar Estado</DropdownMenuLabel>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem 
                            @click="changePostStatus(post.id, 'published')"
                            :disabled="post.status === 'published'"
                          >
                            <CheckCircle class="mr-2 h-4 w-4" />
                            Publicar
                          </DropdownMenuItem>
                          <DropdownMenuItem 
                            @click="changePostStatus(post.id, 'draft')"
                            :disabled="post.status === 'draft'"
                          >
                            <FileText class="mr-2 h-4 w-4" />
                            Borrador
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem 
                            @click="confirmDelete(post)"
                            class="text-red-600 focus:text-red-600"
                          >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Eliminar
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
            
       
            <div v-if="posts.data.length === 0" class="text-center py-12">
              <FileText class="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay publicaciones</h3>
              <p class="text-gray-500 dark:text-gray-400 mb-4">Comienza creando tu primera publicación</p>
              <Button @click="openCreateModal">
                <Plus class="h-4 w-4 mr-2" />
                Nueva Publicación
              </Button>
            </div>
          </div>
          
         
          <div v-if="posts.last_page > 1" class="flex items-center justify-between mt-6">
            <div class="text-sm text-gray-500">
              Mostrando {{ posts.from }} a {{ posts.to }} de {{ posts.total }} resultados
            </div>
            <div class="flex gap-2">
              <Button 
                @click="goToPage(posts.current_page - 1)"
                :disabled="posts.current_page === 1"
                variant="outline"
                size="sm"
              >
                Anterior
              </Button>
              <Button 
                v-for="page in Math.min(5, posts.last_page)" 
                :key="page"
                @click="goToPage(page)"
                :variant="page === posts.current_page ? 'default' : 'outline'"
                size="sm"
              >
                {{ page }}
              </Button>
              <Button 
                @click="goToPage(posts.current_page + 1)"
                :disabled="posts.current_page === posts.last_page"
                variant="outline"
                size="sm"
              >
                Siguiente
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

 
    <Dialog v-model:open="showCreateModal">
      <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ editingPost ? 'Editar Post' : 'Crear Nuevo Post' }}</DialogTitle>
          <DialogDescription>
            {{ editingPost ? 'Modifica los datos del post' : 'Completa los campos para crear un nuevo post' }}
          </DialogDescription>
        </DialogHeader>
        
        <form @submit.prevent="submitPost" class="space-y-6">
      
          <div class="space-y-2">
            <Label for="title">Título *</Label>
            <Input 
              id="title"
              v-model="postForm.title" 
              placeholder="Ingresa el título del post"
              required
              :class="{ 'border-red-500': errors.title }"
            />
            <p v-if="errors.title" class="text-sm text-red-500">{{ errors.title }}</p>
          </div>

         
          <div class="space-y-2">
            <Label for="content">Contenido *</Label>
            <Textarea 
              id="content"
              v-model="postForm.content" 
              placeholder="Escribe el contenido del post..."
              rows="6"
              required
              :class="{ 'border-red-500': errors.content }"
            />
            <p v-if="errors.content" class="text-sm text-red-500">{{ errors.content }}</p>
          </div>

        
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="category">Categoría *</Label>
              <Select v-model="postForm.Category">
                <SelectTrigger :class="{ 'border-red-500': errors.Category }">
                  <SelectValue placeholder="Selecciona una categoría" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="RRHH">RRHH</SelectItem>
                  <SelectItem value="LEGAL">LEGAL</SelectItem>
                  <SelectItem value="GENERAL">GENERAL</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.Category" class="text-sm text-red-500">{{ errors.Category }}</p>
            </div>

            <div class="space-y-2">
              <Label for="status">Estado *</Label>
              <Select v-model="postForm.status">
                <SelectTrigger :class="{ 'border-red-500': errors.status }">
                  <SelectValue placeholder="Selecciona el estado" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="draft">Borrador</SelectItem>
                  <SelectItem value="published">Publicado</SelectItem>
                  <SelectItem value="delete">Eliminado</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.status" class="text-sm text-red-500">{{ errors.status }}</p>
            </div>
          </div>

          
          <div class="space-y-2">
            <Label>Suscripciones Requeridas</Label>
            <div class="flex flex-wrap gap-4">
              <div class="flex items-center space-x-2">
                <Checkbox 
                  id="basica" 
                  :checked="postForm.Subscripcion?.includes('basica')"
                  @update:checked="toggleSubscription('basica')"
                />
                <Label for="basica">Básica</Label>
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox 
                  id="premium" 
                  :checked="postForm.Subscripcion?.includes('premium')"
                  @update:checked="toggleSubscription('premium')"
                />
                <Label for="premium">Premium</Label>
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox 
                  id="empresarial" 
                  :checked="postForm.Subscripcion?.includes('empresarial')"
                  @update:checked="toggleSubscription('empresarial')"
                />
                <Label for="empresarial">Empresarial</Label>
              </div>
            </div>
          </div>

         
          <div class="space-y-2">
            <Label for="image">Imagen</Label>
            <div class="space-y-3">
              <Input 
                id="image"
                type="file" 
                accept="image/*"
                @change="handleImageChange"
                :class="{ 'border-red-500': errors.image_path }"
              />
              <div v-if="imagePreview" class="relative inline-block">
                <img :src="imagePreview" alt="Preview" class="w-32 h-32 object-cover rounded-lg border" />
                <Button 
                  type="button" 
                  variant="destructive" 
                  size="sm" 
                  class="absolute -top-2 -right-2 h-6 w-6 rounded-full p-0"
                  @click="removeImage"
                >
                  ×
                </Button>
              </div>
              <p v-if="errors.image_path" class="text-sm text-red-500">{{ errors.image_path }}</p>
            </div>
          </div>

  
          <div class="space-y-2">
            <Label for="file">Archivo Adjunto</Label>
            <div class="space-y-3">
              <Input 
                id="file"
                type="file" 
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                @change="handleFileChange"
                :class="{ 'border-red-500': errors.file_path }"
              />
              <div v-if="selectedFile" class="flex items-center space-x-2 p-2 bg-gray-50 rounded">
                <FileText class="h-4 w-4" />
                <span class="text-sm">{{ selectedFile.name }}</span>
                <Button 
                  type="button" 
                  variant="ghost" 
                  size="sm" 
                  @click="removeFile"
                >
                  ×
                </Button>
              </div>
              <p v-if="errors.file_path" class="text-sm text-red-500">{{ errors.file_path }}</p>
            </div>
          </div>

         
          <DialogFooter>
            <Button type="button" variant="outline" @click="closeModal">
              Cancelar
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
              {{ editingPost ? 'Actualizar' : 'Crear' }} Post
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

  
    <Dialog v-model:open="showViewModal">
      <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ viewingPost?.title }}</DialogTitle>
          <DialogDescription>
            <div class="flex items-center space-x-4 text-sm text-gray-500">
              <span>Categoría: {{ viewingPost?.Category }}</span>
              <span>Estado: {{ getStatusLabel(viewingPost?.status) }}</span>
              <span>Creado: {{ formatDate(viewingPost?.created_at) }}</span>
            </div>
          </DialogDescription>
        </DialogHeader>
        
        <div v-if="viewingPost" class="space-y-6">
          
          <div v-if="viewingPost.image_url" class="text-center">
            <img 
              :src="viewingPost.image_url" 
              :alt="viewingPost.title"
              class="max-w-full h-auto rounded-lg border"
            />
          </div>
          
       
          <div class="prose max-w-none">
            <div class="whitespace-pre-wrap">{{ viewingPost.content }}</div>
          </div>
          
         
          <div v-if="viewingPost.Subscripcion?.length" class="space-y-2">
            <h4 class="font-medium">Suscripciones Requeridas:</h4>
            <div class="flex flex-wrap gap-2">
              <Badge 
                v-for="sub in viewingPost.Subscripcion" 
                :key="sub"
                variant="secondary"
              >
                {{ sub.charAt(0).toUpperCase() + sub.slice(1) }}
              </Badge>
            </div>
          </div>
          
        
          <div v-if="viewingPost.file_url" class="space-y-2">
            <h4 class="font-medium">Archivo Adjunto:</h4>
            <a 
              :href="viewingPost.file_url" 
              target="_blank"
              class="inline-flex items-center space-x-2 text-blue-600 hover:text-blue-800"
            >
              <FileText class="h-4 w-4" />
              <span>Descargar archivo</span>
              <ExternalLink class="h-4 w-4" />
            </a>
          </div>
        </div>
        
        <DialogFooter>
          <Button @click="showViewModal = false">Cerrar</Button>
          <Button @click="openEditModal(viewingPost)" v-if="viewingPost">
            <Edit class="mr-2 h-4 w-4" />
            Editar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

   
    <AlertDialog v-model:open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
          <AlertDialogDescription>
            Esta acción no se puede deshacer. El post "{{ postToDelete?.title }}" será eliminado permanentemente.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="showDeleteDialog = false">
            Cancelar
          </AlertDialogCancel>
          <AlertDialogAction 
            @click="deletePost"
            class="bg-red-600 hover:bg-red-700"
          >
            Eliminar
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template> -->