import ProductController from './ProductController'
import AlertController from './AlertController'
import Settings from './Settings'
const Controllers = {
    ProductController: Object.assign(ProductController, ProductController),
AlertController: Object.assign(AlertController, AlertController),
Settings: Object.assign(Settings, Settings),
}

export default Controllers