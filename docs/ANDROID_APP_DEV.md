# Driftbottle 安卓 App 开发文档

## 目录
1. [项目概述](#项目概述)
2. [技术架构](#技术架构)
3. [项目结构](#项目结构)
4. [核心功能模块](#核心功能模块)
5. [API接口封装](#api接口封装)
6. [UI组件设计](#ui组件设计)
7. [关键代码示例](#关键代码示例)
8. [开发规范](#开发规范)

---

## 项目概述

### 项目简介
Driftbottle 安卓 App 是 Driftbottle 漂流瓶系统的移动端客户端，采用 Kotlin 语言开发，为用户提供完整的漂流瓶功能体验。

### 功能特性
- 🌊 完整的漂流瓶功能：扔瓶子、捡瓶子
- 🎤 语音漂流瓶支持：录音、播放
- 💬 评论回复系统：支持二级评论
- 👤 用户系统：注册、登录、个人资料
- 💎 VIP会员系统
- 💰 在线充值功能
- 📱 响应式设计，适配各种屏幕
- 🔔 消息推送与通知

### 最低要求
- **Android SDK**: API 24 (Android 7.0)
- **目标SDK**: API 34 (Android 14)
- **Kotlin版本**: 1.9.0+
- **Gradle版本**: 8.0+

---

## 技术架构

### 技术选型

| 类别 | 技术方案 | 说明 |
|-----|---------|-----|
| 开发语言 | Kotlin | 现代 Android 开发首选语言 |
| UI框架 | Jetpack Compose | 现代化声明式 UI |
| 架构模式 | MVVM + Clean Architecture | 清晰的架构分层 |
| 网络请求 | Retrofit + OkHttp | 成熟的网络库组合 |
| 数据解析 | Kotlinx Serialization | Kotlin 原生序列化 |
| 依赖注入 | Hilt | Google 官方依赖注入框架 |
| 异步处理 | Coroutines + Flow | Kotlin 协程 |
| 图片加载 | Coil | Kotlin 友好的图片加载库 |
| 数据存储 | DataStore + Room | 现代数据存储方案 |
| 权限处理 | Activity Result API | 新的权限请求方式 |

### 架构设计

```
app/
├── data/                    # 数据层
│   ├── api/                # API 接口定义
│   ├── model/              # 数据模型
│   ├── repository/         # 数据仓库
│   └── datasource/         # 数据源
├── domain/                  # 领域层
│   ├── usecase/            # 用例
│   └── model/              # 领域模型
├── ui/                      # 表现层
│   ├── theme/              # 主题配置
│   ├── components/         # 通用组件
│   ├── screens/            # 页面
│   └── viewmodel/          # ViewModel
├── di/                      # 依赖注入
└── utils/                   # 工具类
```

---

## 项目结构

### 完整目录结构

```
Driftbottle-Android/
├── app/
│   ├── src/
│   │   ├── main/
│   │   │   ├── java/com/driftbottle/app/
│   │   │   │   ├── data/
│   │   │   │   │   ├── api/
│   │   │   │   │   │   ├── DriftbottleApi.kt
│   │   │   │   │   │   └── ApiService.kt
│   │   │   │   │   ├── model/
│   │   │   │   │   │   ├── request/
│   │   │   │   │   │   └── response/
│   │   │   │   │   ├── repository/
│   │   │   │   │   │   ├── UserRepository.kt
│   │   │   │   │   │   ├── BottleRepository.kt
│   │   │   │   │   │   └── AuthRepository.kt
│   │   │   │   │   └── datasource/
│   │   │   │   │       ├── local/
│   │   │   │   │       └── remote/
│   │   │   │   ├── domain/
│   │   │   │   │   ├── usecase/
│   │   │   │   │   │   ├── LoginUseCase.kt
│   │   │   │   │   │   ├── CreateBottleUseCase.kt
│   │   │   │   │   │   └── PickBottleUseCase.kt
│   │   │   │   │   └── model/
│   │   │   │   ├── ui/
│   │   │   │   │   ├── theme/
│   │   │   │   │   │   ├── Color.kt
│   │   │   │   │   │   ├── Theme.kt
│   │   │   │   │   │   └── Type.kt
│   │   │   │   │   ├── components/
│   │   │   │   │   │   ├── BottleCard.kt
│   │   │   │   │   │   ├── CommentItem.kt
│   │   │   │   │   │   └── VoicePlayer.kt
│   │   │   │   │   ├── screens/
│   │   │   │   │   │   ├── auth/
│   │   │   │   │   │   │   ├── LoginScreen.kt
│   │   │   │   │   │   │   └── RegisterScreen.kt
│   │   │   │   │   │   ├── main/
│   │   │   │   │   │   │   ├── HomeScreen.kt
│   │   │   │   │   │   │   ├── ThrowBottleScreen.kt
│   │   │   │   │   │   │   ├── PickBottleScreen.kt
│   │   │   │   │   │   │   └── ProfileScreen.kt
│   │   │   │   │   │   └── bottle/
│   │   │   │   │   │       ├── BottleDetailScreen.kt
│   │   │   │   │   │       └── UserBottlesScreen.kt
│   │   │   │   │   └── viewmodel/
│   │   │   │   │       ├── LoginViewModel.kt
│   │   │   │   │       ├── BottleViewModel.kt
│   │   │   │   │       └── MainViewModel.kt
│   │   │   │   ├── di/
│   │   │   │   │   ├── AppModule.kt
│   │   │   │   │   ├── NetworkModule.kt
│   │   │   │   │   └── ViewModelModule.kt
│   │   │   │   ├── utils/
│   │   │   │   │   ├── extensions/
│   │   │   │   │   ├── constants/
│   │   │   │   │   └── helpers/
│   │   │   │   └── DriftbottleApplication.kt
│   │   │   ├── res/
│   │   │   │   ├── drawable/
│   │   │   │   ├── values/
│   │   │   │   └── mipmap/
│   │   │   └── AndroidManifest.xml
│   │   └── test/
│   ├── build.gradle.kts
│   └── proguard-rules.pro
├── gradle/
├── build.gradle.kts
├── settings.gradle.kts
├── gradle.properties
└── README.md
```

---

## 核心功能模块

### 1. 用户认证模块

#### 功能说明
- 用户注册
- 用户登录
- 用户登出
- 会话管理
- Token 存储

#### 数据模型

```kotlin
// 登录请求
@Serializable
data class LoginRequest(
    val username: String,
    val password: String
)

// 登录响应
@Serializable
data class LoginResponse(
    val success: Boolean,
    val message: String,
    val user: User? = null
)

// 用户信息
@Serializable
data class User(
    val id: Int,
    val username: String,
    val gender: String,
    val points: Int,
    val experience: Int,
    val level: Int,
    val signature: String? = null,
    val is_vip: Int,
    val vip_level: Int,
    val vip_expire_date: String? = null
)
```

### 2. 漂流瓶模块

#### 功能说明
- 创建文字漂流瓶
- 创建语音漂流瓶
- 随机捡取漂流瓶
- 查看漂流瓶详情
- 评论漂流瓶
- 点赞漂流瓶
- 查看我的瓶子
- 查看我捡到的瓶子

#### 数据模型

```kotlin
// 漂流瓶信息
@Serializable
data class Bottle(
    val id: Int,
    val user_id: Int,
    val content: String,
    val bottle_type: String, // "text" 或 "voice"
    val audio_file: String? = null,
    val audio_duration: Int? = null,
    val is_anonymous: Boolean,
    val status: String,
    val throw_time: String,
    val user_gender: String? = null,
    val likes_count: Int = 0,
    val comments_count: Int = 0,
    val is_liked: Boolean = false
)

// 创建漂流瓶请求
@Serializable
data class CreateBottleRequest(
    val content: String,
    val is_anonymous: Boolean,
    val bottle_type: String = "text",
    val audio_file: String? = null,
    val audio_duration: Int? = null
)

// 评论请求
@Serializable
data class CommentRequest(
    val bottle_id: Int,
    val content: String,
    val parent_id: Int? = null,
    val reply_to_user_id: Int? = null,
    val throw_back: Boolean = true
)

// 评论信息
@Serializable
data class Comment(
    val id: Int,
    val bottle_id: Int,
    val user_id: Int,
    val username: String,
    val content: String,
    val parent_id: Int? = null,
    val reply_to_user_id: Int? = null,
    val reply_to_username: String? = null,
    val created_at: String,
    val replies: List<Comment> = emptyList()
)
```

### 3. 语音模块

#### 功能说明
- 录音功能
- 播放功能
- 音频上传
- 音频文件管理

#### 权限要求
```xml
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
```

### 4. 个人中心模块

#### 功能说明
- 查看个人资料
- 编辑个人资料
- 查看积分和等级
- VIP会员开通
- 在线充值
- 查看充值记录
- 消息中心
- 签到功能

---

## API接口封装

### 基础配置

#### Retrofit 配置

```kotlin
// di/NetworkModule.kt
@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {
    
    private const val BASE_URL = "https://your-driftbottle-api.com/"
    
    @Provides
    @Singleton
    fun provideOkHttpClient(): OkHttpClient {
        return OkHttpClient.Builder()
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .addInterceptor(HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BODY
            })
            .addInterceptor { chain ->
                val original = chain.request()
                val requestBuilder = original.newBuilder()
                    .header("Content-Type", "application/json")
                chain.proceed(requestBuilder.build())
            }
            .build()
    }
    
    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient): Retrofit {
        val contentType = "application/json".toMediaType()
        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(
                Json {
                    ignoreUnknownKeys = true
                    isLenient = true
                }.asConverterFactory(contentType)
            )
            .build()
    }
    
    @Provides
    @Singleton
    fun provideApiService(retrofit: Retrofit): DriftbottleApi {
        return retrofit.create(DriftbottleApi::class.java)
    }
}
```

### API 接口定义

```kotlin
// data/api/DriftbottleApi.kt
interface DriftbottleApi {
    
    // 用户认证
    @POST("api.php")
    suspend fun login(
        @Query("action") action: String = "login",
        @Body request: LoginRequest
    ): Response<LoginResponse>
    
    @POST("api.php")
    suspend fun register(
        @Query("action") action: String = "register",
        @Body request: RegisterRequest
    ): Response<RegisterResponse>
    
    @POST("api.php")
    suspend fun logout(
        @Query("action") action: String = "logout"
    ): Response<BaseResponse>
    
    @GET("api.php")
    suspend fun checkAuth(
        @Query("action") action: String = "check_auth"
    ): Response<CheckAuthResponse>
    
    // 漂流瓶
    @POST("api.php")
    suspend fun createBottle(
        @Query("action") action: String = "create_bottle",
        @Body request: CreateBottleRequest
    ): Response<CreateBottleResponse>
    
    @POST("api.php")
    suspend fun pickBottle(
        @Query("action") action: String = "pick_bottle"
    ): Response<PickBottleResponse>
    
    @POST("api.php")
    suspend fun commentBottle(
        @Query("action") action: String = "comment_bottle",
        @Body request: CommentRequest
    ): Response<CommentResponse>
    
    @POST("api.php")
    suspend fun likeBottle(
        @Query("action") action: String = "like_bottle",
        @Body request: LikeRequest
    ): Response<LikeResponse>
    
    @GET("api.php")
    suspend fun getUserBottles(
        @Query("action") action: String = "user_bottles",
        @Query("page") page: Int = 1,
        @Query("page_size") pageSize: Int = 10
    ): Response<BottlesResponse>
    
    @GET("api.php")
    suspend fun getUserPickedBottles(
        @Query("action") action: String = "user_picked_bottles",
        @Query("page") page: Int = 1,
        @Query("page_size") pageSize: Int = 10
    ): Response<BottlesResponse>
    
    // 系统
    @GET("api.php")
    suspend fun getAnnouncements(
        @Query("action") action: String = "get_announcements"
    ): Response<AnnouncementsResponse>
    
    @GET("api.php")
    suspend fun getBasicSettings(
        @Query("action") action: String = "get_basic_settings"
    ): Response<SettingsResponse>
    
    // 支付
    @GET("api.php")
    suspend fun getPaymentConfig(
        @Query("action") action: String = "get_payment_config"
    ): Response<PaymentConfigResponse>
    
    @POST("api.php")
    suspend fun createRechargeOrder(
        @Query("action") action: String = "create_recharge_order",
        @Body request: RechargeOrderRequest
    ): Response<RechargeOrderResponse>
    
    @Multipart
    @POST("api.php")
    suspend fun uploadAudio(
        @Query("action") action: String = "upload_audio",
        @Part audio: MultipartBody.Part
    ): Response<UploadAudioResponse>
}
```

### 仓库层实现

```kotlin
// data/repository/AuthRepository.kt
class AuthRepository @Inject constructor(
    private val api: DriftbottleApi,
    private val dataStore: DataStore<Preferences>
) {
    
    suspend fun login(username: String, password: String): Result<LoginResponse> {
        return try {
            val request = LoginRequest(username, password)
            val response = api.login(request = request)
            if (response.isSuccessful && response.body()?.success == true) {
                response.body()?.user?.let { saveUser(it) }
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "登录失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun register(username: String, password: String, gender: String): Result<RegisterResponse> {
        return try {
            val request = RegisterRequest(username, password, gender)
            val response = api.register(request = request)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "注册失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    private suspend fun saveUser(user: User) {
        dataStore.edit { preferences ->
            preferences[USER_ID] = user.id
            preferences[USERNAME] = user.username
            preferences[IS_LOGGED_IN] = true
        }
    }
    
    companion object {
        val USER_ID = intPreferencesKey("user_id")
        val USERNAME = stringPreferencesKey("username")
        val IS_LOGGED_IN = booleanPreferencesKey("is_logged_in")
    }
}
```

---

## UI组件设计

### 主题配置

```kotlin
// ui/theme/Color.kt
val Primary = Color(0xFF6366F1)
val PrimaryDark = Color(0xFF4F46E5)
val Secondary = Color(0xFFEC4899)
val AccentBlue = Color(0xFF3B82F6)
val AccentPink = Color(0xFFF472B6)
val Background = Color(0xFFF8FAFC)
val Surface = Color(0xFFFFFFFF)
val TextPrimary = Color(0xFF1E293B)
val TextSecondary = Color(0xFF64748B)

// ui/theme/Theme.kt
@Composable
fun DriftbottleTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colors = if (darkTheme) {
        darkColors(
            primary = Primary,
            primaryVariant = PrimaryDark,
            secondary = Secondary,
            background = Color(0xFF0F172A),
            surface = Color(0xFF1E293B)
        )
    } else {
        lightColors(
            primary = Primary,
            primaryVariant = PrimaryDark,
            secondary = Secondary,
            background = Background,
            surface = Surface
        )
    }
    
    MaterialTheme(
        colors = colors,
        typography = Typography,
        shapes = Shapes,
        content = content
    )
}
```

### 漂流瓶卡片组件

```kotlin
// ui/components/BottleCard.kt
@Composable
fun BottleCard(
    bottle: Bottle,
    onCommentClick: (Bottle) -> Unit,
    onLikeClick: (Bottle) -> Unit,
    modifier: Modifier = Modifier
) {
    val isMale = bottle.user_gender == "男"
    val cardColor = if (isMale) AccentBlue else AccentPink
    
    Card(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 8.dp),
        elevation = 4.dp,
        shape = RoundedCornerShape(16.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        imageVector = if (isMale) Icons.Default.Male else Icons.Default.Female,
                        contentDescription = null,
                        tint = cardColor,
                        modifier = Modifier.size(24.dp)
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = if (bottle.is_anonymous) "匿名用户" else bottle.user_gender ?: "用户",
                        style = MaterialTheme.typography.subtitle2,
                        color = TextSecondary
                    )
                }
                Text(
                    text = formatDateTime(bottle.throw_time),
                    style = MaterialTheme.typography.caption,
                    color = TextSecondary
                )
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            when (bottle.bottle_type) {
                "voice" -> {
                    VoicePlayer(
                        audioFile = bottle.audio_file ?: "",
                        duration = bottle.audio_duration ?: 0
                    )
                }
                else -> {
                    Text(
                        text = bottle.content,
                        style = MaterialTheme.typography.body1,
                        color = TextPrimary,
                        lineHeight = 1.6.em
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.End
            ) {
                IconButton(onClick = { onLikeClick(bottle) }) {
                    Icon(
                        imageVector = if (bottle.is_liked) Icons.Default.Favorite else Icons.Default.FavoriteBorder,
                        contentDescription = "点赞",
                        tint = if (bottle.is_liked) Color.Red else TextSecondary
                    )
                }
                Text(
                    text = bottle.likes_count.toString(),
                    modifier = Modifier.align(Alignment.CenterVertically),
                    style = MaterialTheme.typography.caption
                )
                
                Spacer(modifier = Modifier.width(16.dp))
                
                IconButton(onClick = { onCommentClick(bottle) }) {
                    Icon(
                        imageVector = Icons.Default.ChatBubbleOutline,
                        contentDescription = "评论",
                        tint = TextSecondary
                    )
                }
                Text(
                    text = bottle.comments_count.toString(),
                    modifier = Modifier.align(Alignment.CenterVertically),
                    style = MaterialTheme.typography.caption
                )
            }
        }
    }
}
```

### 语音播放器组件

```kotlin
// ui/components/VoicePlayer.kt
@Composable
fun VoicePlayer(
    audioFile: String,
    duration: Int,
    modifier: Modifier = Modifier
) {
    var isPlaying by remember { mutableStateOf(false) }
    var currentPosition by remember { mutableStateOf(0) }
    
    val context = LocalContext.current
    val mediaPlayer = remember { MediaPlayer() }
    
    DisposableEffect(Unit) {
        onDispose {
            if (mediaPlayer.isPlaying) {
                mediaPlayer.stop()
            }
            mediaPlayer.release()
        }
    }
    
    Surface(
        modifier = modifier
            .fillMaxWidth()
            .height(56.dp),
        color = Color(0xFFF1F5F9),
        shape = RoundedCornerShape(28.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(
                onClick = {
                    if (isPlaying) {
                        mediaPlayer.pause()
                    } else {
                        try {
                            mediaPlayer.reset()
                            mediaPlayer.setDataSource(audioFile)
                            mediaPlayer.prepare()
                            mediaPlayer.start()
                        } catch (e: Exception) {
                            e.printStackTrace()
                        }
                    }
                    isPlaying = !isPlaying
                }
            ) {
                Icon(
                    imageVector = if (isPlaying) Icons.Default.Pause else Icons.Default.PlayArrow,
                    contentDescription = if (isPlaying) "暂停" else "播放",
                    tint = Primary
                )
            }
            
            Spacer(modifier = Modifier.width(12.dp))
            
            Slider(
                value = currentPosition.toFloat(),
                valueRange = 0f..duration.toFloat(),
                onValueChange = { currentPosition = it.toInt() },
                modifier = Modifier.weight(1f)
            )
            
            Spacer(modifier = Modifier.width(12.dp))
            
            Text(
                text = formatDuration(currentPosition) + " / " + formatDuration(duration),
                style = MaterialTheme.typography.caption,
                color = TextSecondary
            )
        }
    }
}

private fun formatDuration(seconds: Int): String {
    val mins = seconds / 60
    val secs = seconds % 60
    return String.format("%02d:%02d", mins, secs)
}
```

---

## 关键代码示例

### 登录页面实现

```kotlin
// ui/screens/auth/LoginScreen.kt
@Composable
fun LoginScreen(
    viewModel: LoginViewModel = hiltViewModel(),
    onLoginSuccess: () -> Unit,
    onNavigateToRegister: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    
    var username by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }
    
    LaunchedEffect(uiState) {
        if (uiState is LoginUiState.Success) {
            onLoginSuccess()
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("登录") },
                backgroundColor = MaterialTheme.colors.surface
            )
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(24.dp),
            contentAlignment = Alignment.Center
        ) {
            Column(
                modifier = Modifier.fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(
                    imageVector = Icons.Default.AccountCircle,
                    contentDescription = null,
                    modifier = Modifier.size(120.dp),
                    tint = MaterialTheme.colors.primary
                )
                
                Spacer(modifier = Modifier.height(32.dp))
                
                OutlinedTextField(
                    value = username,
                    onValueChange = { username = it },
                    label = { Text("用户名") },
                    leadingIcon = {
                        Icon(Icons.Default.Person, contentDescription = null)
                    },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    enabled = uiState !is LoginUiState.Loading
                )
                
                Spacer(modifier = Modifier.height(16.dp))
                
                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    label = { Text("密码") },
                    leadingIcon = {
                        Icon(Icons.Default.Lock, contentDescription = null)
                    },
                    trailingIcon = {
                        IconButton(onClick = { passwordVisible = !passwordVisible }) {
                            Icon(
                                imageVector = if (passwordVisible) 
                                    Icons.Default.Visibility else Icons.Default.VisibilityOff,
                                contentDescription = if (passwordVisible) "隐藏密码" else "显示密码"
                            )
                        }
                    },
                    visualTransformation = if (passwordVisible) 
                        VisualTransformation.None else PasswordVisualTransformation(),
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    enabled = uiState !is LoginUiState.Loading
                )
                
                Spacer(modifier = Modifier.height(24.dp))
                
                Button(
                    onClick = { viewModel.login(username, password) },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(56.dp),
                    enabled = username.isNotEmpty() && 
                            password.isNotEmpty() && 
                            uiState !is LoginUiState.Loading
                ) {
                    if (uiState is LoginUiState.Loading) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(24.dp),
                            color = MaterialTheme.colors.onPrimary,
                            strokeWidth = 2.dp
                        )
                    } else {
                        Text("登录")
                    }
                }
                
                Spacer(modifier = Modifier.height(16.dp))
                
                TextButton(onClick = onNavigateToRegister) {
                    Text("还没有账号？立即注册")
                }
            }
        }
    }
    
    if (uiState is LoginUiState.Error) {
        LaunchedEffect(Unit) {
            Toast.makeText(
                LocalContext.current,
                (uiState as LoginUiState.Error).message,
                Toast.LENGTH_SHORT
            ).show()
        }
    }
}
```

### 登录 ViewModel

```kotlin
// ui/viewmodel/LoginViewModel.kt
@HiltViewModel
class LoginViewModel @Inject constructor(
    private val loginUseCase: LoginUseCase
) : ViewModel() {
    
    private val _uiState = MutableStateFlow<LoginUiState>(LoginUiState.Idle)
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()
    
    fun login(username: String, password: String) {
        viewModelScope.launch {
            _uiState.value = LoginUiState.Loading
            
            loginUseCase(username, password)
                .onSuccess {
                    _uiState.value = LoginUiState.Success
                }
                .onFailure { error ->
                    _uiState.value = LoginUiState.Error(error.message ?: "登录失败")
                }
        }
    }
}

sealed interface LoginUiState {
    object Idle : LoginUiState
    object Loading : LoginUiState
    object Success : LoginUiState
    data class Error(val message: String) : LoginUiState
}
```

### 用例实现

```kotlin
// domain/usecase/LoginUseCase.kt
class LoginUseCase @Inject constructor(
    private val authRepository: AuthRepository
) {
    suspend operator fun invoke(username: String, password: String): Result<Unit> {
        return authRepository.login(username, password).map { }
    }
}
```

### 扔漂流瓶页面

```kotlin
// ui/screens/main/ThrowBottleScreen.kt
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ThrowBottleScreen(
    viewModel: BottleViewModel = hiltViewModel(),
    onBack: () -> Unit
) {
    val context = LocalContext.current
    val uiState by viewModel.throwBottleUiState.collectAsStateWithLifecycle()
    
    var content by remember { mutableStateOf("") }
    var isAnonymous by remember { mutableStateOf(true) }
    var bottleType by remember { mutableStateOf("text") }
    var audioFile by remember { mutableStateOf<String?>(null) }
    var audioDuration by remember { mutableStateOf(0) }
    var isRecording by remember { mutableStateOf(false) }
    
    val maxLength = 500
    
    LaunchedEffect(uiState) {
        if (uiState is ThrowBottleUiState.Success) {
            Toast.makeText(context, "漂流瓶已扔出！", Toast.LENGTH_SHORT).show()
            onBack()
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("扔漂流瓶") },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "返回")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp)
        ) {
            TabRow(
                selectedTabIndex = if (bottleType == "text") 0 else 1
            ) {
                Tab(
                    selected = bottleType == "text",
                    onClick = { bottleType = "text" },
                    text = { Text("文字") },
                    icon = { Icon(Icons.Default.TextFields, contentDescription = null) }
                )
                Tab(
                    selected = bottleType == "voice",
                    onClick = { bottleType = "voice" },
                    text = { Text("语音") },
                    icon = { Icon(Icons.Default.Mic, contentDescription = null) }
                )
            }
            
            Spacer(modifier = Modifier.height(24.dp))
            
            if (bottleType == "text") {
                OutlinedTextField(
                    value = content,
                    onValueChange = { if (it.length <= maxLength) content = it },
                    label = { Text("写下你的心情...") },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(200.dp),
                    maxLines = 10,
                    placeholder = { Text("分享你的故事...") }
                )
                
                Text(
                    text = "${content.length}/$maxLength",
                    modifier = Modifier.align(Alignment.End),
                    style = MaterialTheme.typography.caption,
                    color = if (content.length >= maxLength) Color.Red else TextSecondary
                )
            } else {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    if (audioFile != null) {
                        VoicePlayer(
                            audioFile = audioFile!!,
                            duration = audioDuration
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        TextButton(onClick = {
                            audioFile = null
                            audioDuration = 0
                        }) {
                            Text("重新录制")
                        }
                    } else {
                        Button(
                            onClick = { isRecording = !isRecording },
                            modifier = Modifier.size(120.dp),
                            shape = CircleShape,
                            colors = ButtonDefaults.buttonColors(
                                backgroundColor = if (isRecording) Color.Red else Primary
                            )
                        ) {
                            Icon(
                                imageVector = if (isRecording) Icons.Default.Stop else Icons.Default.Mic,
                                contentDescription = if (isRecording) "停止" else "录制",
                                modifier = Modifier.size(48.dp)
                            )
                        }
                        Spacer(modifier = Modifier.height(16.dp))
                        Text(
                            text = if (isRecording) "正在录制..." else "点击录制",
                            style = MaterialTheme.typography.body1
                        )
                    }
                }
            }
            
            Spacer(modifier = Modifier.height(24.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Checkbox(
                    checked = isAnonymous,
                    onCheckedChange = { isAnonymous = it }
                )
                Text("匿名发送")
            }
            
            Spacer(modifier = Modifier.weight(1f))
            
            Button(
                onClick = {
                    viewModel.createBottle(
                        content = if (bottleType == "text") content else "语音漂流瓶",
                        isAnonymous = isAnonymous,
                        bottleType = bottleType,
                        audioFile = audioFile,
                        audioDuration = audioDuration
                    )
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                enabled = (bottleType == "text" && content.isNotEmpty()) ||
                        (bottleType == "voice" && audioFile != null)
            ) {
                if (uiState is ThrowBottleUiState.Loading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(24.dp),
                        color = MaterialTheme.colors.onPrimary
                    )
                } else {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Send, contentDescription = null)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("扔出漂流瓶")
                    }
                }
            }
        }
    }
}
```

---

## 开发规范

### 代码风格
- 遵循 Kotlin 官方编码规范
- 使用 4 空格缩进
- 类名使用大驼峰命名法
- 函数和变量使用小驼峰命名法
- 常量使用全大写下划线分隔

### Git 提交规范
```
<type>(<scope>): <subject>

类型：
- feat: 新功能
- fix: 修复bug
- docs: 文档更新
- style: 代码格式调整
- refactor: 重构
- test: 测试相关
- chore: 构建/工具相关
```

### 性能优化建议
1. 使用 Kotlin 协程处理异步操作
2. 合理使用 Flow 进行数据流管理
3. 图片使用 Coil 加载并缓存
4. 列表使用 LazyColumn/LazyRow
5. 避免在 Compose 重组中执行耗时操作
6. 使用 remember 缓存计算结果

### 测试策略
- 单元测试：测试 UseCase 和 Repository
- UI 测试：使用 Compose Testing
- 集成测试：测试 API 接口调用

---

## 总结

本文档提供了 Driftbottle 安卓 App 的完整开发指南，包括：

- 完整的技术架构设计
- 详细的项目结构规划
- 核心功能模块的实现方案
- API 接口的封装和使用
- UI 组件的设计和实现
- 关键代码示例
- 开发规范和最佳实践

开发者可以根据本文档快速启动项目开发，实现完整的漂流瓶移动端应用。
