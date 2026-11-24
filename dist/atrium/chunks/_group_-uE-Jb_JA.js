import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, f as a, k as o, r as s } from "./catalog-DCoO3A8a.js";
import { u as c, y as l } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as u, AtriumPageHeader as d, AtriumState as f, navigateTo as p } from "@refactor-circus/atrium";
import { UBadge as m, UButton as h, UCard as g, UFormField as _, UInput as v, ULink as y, UTable as b } from "@refactor-circus/atrium/ui";
import { computed as x, createCommentVNode as ee, createElementBlock as S, createElementVNode as C, createTextVNode as w, createVNode as T, defineComponent as E, openBlock as D, reactive as O, toDisplayString as k, unref as A, watch as j, withCtx as M, withModifiers as N } from "vue";
import { useI18n as P } from "vue-i18n";
import { useRoute as F } from "vue-router";
// src/pages/attribute-groups/[group].vue?vue&type=script&setup=true&lang.ts
var I = {
	class: "space-y-4",
	"data-testid": "showroom-attribute-group"
}, L = {
	key: 0,
	class: "space-y-5"
}, R = { class: "font-semibold" }, z = { class: "font-semibold" }, B = ["data-testid"], V = /* @__PURE__ */ E({
	__name: "[group]",
	setup(E) {
		let { t: V, locale: H } = P(), U = F(), W = i(), G = x(() => String(U.params.group)), { client: K, data: q, pending: J, failed: Y, status: X, reload: Z } = o(async (e) => n(await e.GET("/attribute-groups/{group}", { params: { path: { group: G.value } } })).data, G), Q = x(() => q.value), te = x(() => Q.value?.attributes ?? []), ne = x(() => [
			{
				accessorKey: "code",
				header: V("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: V("showroom.catalog.fields.label")
			},
			{
				accessorKey: "type",
				header: V("showroom.catalog.fields.type")
			}
		]), $ = O({
			label: "",
			sort_order: "0"
		});
		j(q, () => {
			Q.value && ($.label = Q.value.labels[H.value] ?? "", $.sort_order = String(Q.value.sort_order));
		}, { immediate: !0 });
		async function re() {
			let e = Q.value;
			if (!K || !e) return;
			let t = {
				labels: c(e.labels, H.value, $.label),
				sort_order: l($.sort_order) ?? 0
			};
			await W.run(async () => n(await K.PATCH("/attribute-groups/{group}", {
				params: { path: { group: G.value } },
				body: r(t)
			}))) && await Z();
		}
		async function ie() {
			K && window.confirm(V("showroom.structure.groups.confirm_delete")) && await W.run(async () => n(await K.DELETE("/attribute-groups/{group}", { params: { path: { group: G.value } } }))) && await p("/showroom/attribute-groups");
		}
		return (n, r) => {
			let i = h, o = d, c = v, l = _, p = g, x = f, E = y, O = m, j = b, P = u;
			return D(), S("div", I, [T(P, {
				"screen-key": "showroom.attribute-group",
				record: A(G)
			}, {
				default: M(() => [T(t, {
					ready: !A(J) && A(Q) !== null,
					failed: A(Y),
					status: A(X)
				}, {
					default: M(() => [A(Q) ? (D(), S("div", L, [
						T(o, {
							title: A(a)(A(Q).labels, A(H), A(Q).code),
							description: A(Q).code,
							"data-testid": "showroom-group-title"
						}, {
							default: M(() => [T(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: A(W).busy.value,
								"data-testid": "showroom-group-delete",
								onClick: ie
							}, {
								default: M(() => [w(k(A(V)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["title", "description"]),
						T(e, { action: A(W) }, null, 8, ["action"]),
						T(p, null, {
							header: M(() => [C("h2", R, k(A(V)("showroom.structure.details")), 1)]),
							default: M(() => [C("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-group-form",
								onSubmit: N(re, ["prevent"])
							}, [
								T(l, {
									label: A(V)("showroom.catalog.fields.label_in", { locale: A(H) }),
									error: A(W).error("labels")
								}, {
									default: M(() => [T(c, {
										modelValue: A($).label,
										"onUpdate:modelValue": r[0] ||= (e) => A($).label = e,
										class: "w-64",
										"data-testid": "showroom-group-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								T(l, {
									label: A(V)("showroom.catalog.fields.sort_order"),
									error: A(W).error("sort_order")
								}, {
									default: M(() => [T(c, {
										modelValue: A($).sort_order,
										"onUpdate:modelValue": r[1] ||= (e) => A($).sort_order = e,
										type: "number",
										min: "0",
										class: "w-28",
										"data-testid": "showroom-group-sort"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								T(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: A(W).busy.value,
									"data-testid": "showroom-group-save"
								}, {
									default: M(() => [w(k(A(V)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)]),
							_: 1
						}),
						T(p, null, {
							header: M(() => [C("h2", z, k(A(V)("showroom.structure.groups.members")), 1)]),
							default: M(() => [T(j, {
								data: A(te),
								columns: A(ne)
							}, {
								empty: M(() => [T(x, {
									compact: "",
									title: A(V)("showroom.structure.groups.no_members"),
									"data-testid": "showroom-group-attributes-empty"
								}, null, 8, ["title"])]),
								"code-cell": M(({ row: { original: e } }) => [C("span", { "data-testid": `showroom-group-attribute-${e.code}` }, [T(E, {
									to: A(s)(e.code),
									class: "font-mono hover:underline"
								}, {
									default: M(() => [w(k(e.code), 1)]),
									_: 2
								}, 1032, ["to"])], 8, B)]),
								"label-cell": M(({ row: { original: e } }) => [w(k(A(a)(e.labels, A(H), e.code)), 1)]),
								"type-cell": M(({ row: { original: e } }) => [T(O, {
									color: "neutral",
									variant: "subtle"
								}, {
									default: M(() => [w(k(e.type), 1)]),
									_: 2
								}, 1024)]),
								_: 1
							}, 8, ["data", "columns"])]),
							_: 1
						})
					])) : ee("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { V as default };
