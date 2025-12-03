db = db.getSiblingDB(process.env.MONGODB_DB || 'cinephoria_mongo');

db.createUser({
  user: process.env.ME_USER || 'cinephoria_app',
  pwd:  process.env.ME_PASS || 'motdepasse_app_bien_sale',
  roles: [
    { role: "readWrite", db: process.env.MONGODB_DB || 'cinephoria_mongo' }
  ]
});
