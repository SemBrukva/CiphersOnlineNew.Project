import { defineConfig } from 'vite'
import { resolve } from 'path'
import { writeFileSync, existsSync, unlinkSync, mkdirSync } from 'fs'

export default defineConfig(({ command }) => ({
    publicDir: false,
    base: command === 'serve' ? '/' : '/build/',

    server: {
        cors: true,
        origin: 'http://localhost:5173',
    },

    build: {
        outDir: 'public/build',
        manifest: true,
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app:   resolve(__dirname, 'private/resources/js/app.js'),
                admin: resolve(__dirname, 'private/resources/js/admin.js'),
            },
        },
    },

    plugins: [
        {
            // Создаёт public/build/hot во время dev-сервера; удаляет при остановке.
            // PHP-хелпер ViteAssets читает этот файл для переключения dev/prod режимов.
            name: 'hot-file',
            configureServer(server) {
                const hotFile = resolve(__dirname, 'public/build/hot')

                server.httpServer?.once('listening', () => {
                    const addr = server.httpServer.address()
                    mkdirSync(resolve(__dirname, 'public/build'), { recursive: true })
                    writeFileSync(hotFile, `http://localhost:${addr.port}`)
                })

                const cleanup = () => {
                    if (existsSync(hotFile)) unlinkSync(hotFile)
                }
                process.once('exit',   cleanup)
                process.once('SIGINT',  () => { cleanup(); process.exit() })
                process.once('SIGTERM', () => { cleanup(); process.exit() })
            },
        },
    ],
}))
