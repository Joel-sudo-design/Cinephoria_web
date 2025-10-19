db = db.getSiblingDB(process.env.MONGODB_DB || 'cinephoria_mongo');
db.createUser({
    user: process.env.ME_USER || 'admin',
    pwd:  process.env.ME_PASS || 'admin',
    roles: [{ role: "readWrite", db: process.env.MONGODB_DB || 'cinephoria_mongo' }]
});
