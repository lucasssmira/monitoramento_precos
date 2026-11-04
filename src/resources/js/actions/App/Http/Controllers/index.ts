import ProductController from './ProductController'
import Settings from './Settings'
const Controllers = {
    ProductController: Object.assign(ProductController, ProductController),
Settings: Object.assign(Settings, Settings),
}

export default Controllers