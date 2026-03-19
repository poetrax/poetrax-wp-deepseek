# Poetrax API Documentation

## Базовый URL
`https://poetrax.online/api`

## Эндпоинты

### Треки
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/tracks` | Список треков |
| GET | `/tracks/{id}` | Детали трека |
| GET | `/tracks/popular` | Популярные |
| GET | `/tracks/recent` | Новые |
| POST | `/tracks/{id}/play` | Прослушивание |
| POST | `/tracks/{id}/like` | Лайк |
| DELETE | `/tracks/{id}/like` | Убрать лайк |

### Поэты
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/poets` | Список поэтов |
| GET | `/poets/{id}` | Детали |
| GET | `/poets/popular` | Популярные |
