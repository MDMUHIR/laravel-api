GET|HEAD / .....................................................................................................................................
POST \_ignition/execute-solution .............................. ignition.executeSolution › Spatie\LaravelIgnition › ExecuteSolutionController
GET|HEAD \_ignition/health-check .......................................... ignition.healthCheck › Spatie\LaravelIgnition › HealthCheckController
POST \_ignition/update-config ....................................... ignition.updateConfig › Spatie\LaravelIgnition › UpdateConfigController
POST api/admin/blog-categories/add .......................................................................... BlogController@addBlogCategory
DELETE api/admin/blog-categories/delete/{id} ............................................................... BlogController@deleteBlogCategory
POST api/admin/blog-categories/update .................................................................... BlogController@updateBlogCategory
POST api/admin/blog-tags/add ..................................................................................... BlogController@addBlogTag
DELETE api/admin/blog-tags/delete/{id} .......................................................................... BlogController@deleteBlogTag
POST api/admin/blog-tags/update ............................................................................... BlogController@updateBlogTag
GET|HEAD api/admin/blogs ............................................................................................... BlogController@getBlogs
POST api/admin/blogs/add ............................................................................................ BlogController@addBlog
DELETE api/admin/blogs/delete/{id} ................................................................................. BlogController@deleteBlog
POST api/admin/blogs/update ...................................................................................... BlogController@updateBlog
POST api/admin/categories/add ............................................................................... CategoryController@addCategory
DELETE api/admin/categories/delete/{id} .................................................................... CategoryController@deleteCategory
GET|HEAD api/admin/coupon .......................................................................................... CouponController@getCoupons
POST api/admin/coupon/add ....................................................................................... CouponController@addCoupon
DELETE api/admin/coupon/delete/{id} ............................................................................ CouponController@deleteCoupon
POST api/admin/coupon/update ................................................................................. CouponController@updateCoupon
GET|HEAD api/admin/orders ....................................................................................... OrderController@getAdminOrders
POST api/admin/orders/update .............................................................................. OrderController@updateAdminOrder
GET|HEAD api/admin/orders/{id} ................................................................................... OrderController@getAdminOrder
POST api/admin/products/add ................................................................................... ProductController@addProduct
DELETE api/admin/products/delete/{id} ........................................................................ ProductController@deleteProduct
POST api/admin/products/update ............................................................................. ProductController@updateProduct
DELETE api/admin/products/{productId}/images/{imageId} .................................................. ProductController@deleteProductImage
GET|HEAD api/admin/users ............................................................................................... AuthController@getUsers
POST api/admin/users/add ............................................................................................ AuthController@addUser
DELETE api/admin/users/delete/{id} ................................................................................. AuthController@deleteUser
POST api/admin/users/update ...................................................................................... AuthController@updateUser
GET|HEAD api/auth/google ....................................................................................... AuthController@redirectToGoogle
GET|HEAD api/auth/google/callback .......................................................................... AuthController@handleGoogleCallback
GET|HEAD api/blog-categories .................................................................................. BlogController@getBlogCategories
GET|HEAD api/blog-tags .............................................................................................. BlogController@getBlogTags
GET|HEAD api/blogs ..................................................................................................... BlogController@getBlogs
GET|HEAD api/blogs/{id} ........................................................................................... BlogController@getSingleBlog
GET|HEAD api/cart ....................................................................................................... CartController@getCart
POST api/cart/add ................................................................................................. CartController@addToCart
DELETE api/cart/delete/{id} ........................................................................................ CartController@deleteCart
POST api/cart/update ............................................................................................. CartController@updateCart
GET|HEAD api/categories ....................................................................................... CategoryController@getCategories
POST api/login ........................................................................................................ AuthController@login
GET|HEAD api/logout ...................................................................................................... AuthController@logout
GET|HEAD api/orders ................................................................................................... OrderController@getOrder
POST api/orders/add ............................................................................................... OrderController@addOrder
GET|HEAD api/products ............................................................................................ ProductController@getProducts
GET|HEAD api/products/search .................................................................................. ProductController@searchProducts
GET|HEAD api/products/{id} .................................................................................. ProductController@getSingleProduct
POST api/register .................................................................................................. AuthController@register
GET|HEAD api/user .......................................................................................................... AuthController@user
POST api/verify-coupon ....................................................................................... CouponController@verifyCoupon
GET|HEAD api/wishlist ........................................................................................... WishListController@getWishList
POST api/wishlist/add ..................................................................................... WishListController@addToWishList
DELETE api/wishlist/delete/{id} ............................................................................ WishListController@deleteWishList
GET|HEAD sanctum/csrf-cookie ................................................. sanctum.csrf-cookie › Laravel\Sanctum › CsrfCookieController@show
